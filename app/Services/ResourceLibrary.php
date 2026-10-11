<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\PointAction;
use App\Enums\ResourceStatus;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Resource;
use App\Models\ResourceReview;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dev Resources (cahier, sections 6 and 7): publishing, likes, ratings and comments.
 * Images are public; files are private and served through a download route that counts downloads.
 */
class ResourceLibrary
{
    public function __construct(private DevScore $devScore) {}

    /**
     * @param  array<string, mixed>  $data  Resource fields + 'skills' (ids) + 'tags' (names)
     */
    public function publish(User $author, array $data, ?UploadedFile $image = null, ?UploadedFile $file = null): Resource
    {
        return DB::transaction(function () use ($author, $data, $image, $file) {
            $resource = $author->resources()->create(array_merge(
                Arr::except($data, ['skills', 'tags']),
                ['image_path' => $this->storeImage($image), 'file_path' => $this->storeFile($file)],
            ));

            // Published right away (team decision); an admin can take it down later.
            $resource->forceFill(['status' => ResourceStatus::Approved])->save();

            $resource->skills()->sync($data['skills'] ?? []);
            $resource->tags()->sync($this->tagIds($data['tags'] ?? []));

            $this->devScore->award($author, PointAction::ResourcePublished, $resource);
            Activity::log($author, ActivityType::ResourcePublished, $resource);

            return $resource;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Resource fields + 'skills' + 'tags' + 'remove_image' + 'remove_file'
     */
    public function update(Resource $resource, array $data, ?UploadedFile $image = null, ?UploadedFile $file = null): Resource
    {
        $fields = Arr::except($data, ['skills', 'tags', 'remove_image', 'remove_file']);

        if ($image || ($data['remove_image'] ?? false)) {
            $this->delete('public', $resource->image_path);
            $fields['image_path'] = $this->storeImage($image);
        }

        if ($file || ($data['remove_file'] ?? false)) {
            $this->delete('local', $resource->file_path);
            $fields['file_path'] = $this->storeFile($file);
        }

        $resource->update($fields);
        $resource->skills()->sync($data['skills'] ?? []);
        $resource->tags()->sync($this->tagIds($data['tags'] ?? []));

        return $resource;
    }

    /**
     * "Ressource appréciée": +5 for the author on the first like of each member.
     */
    public function like(User $user, Resource $resource): void
    {
        if ($resource->likers()->whereKey($user->id)->exists()) {
            return;
        }

        $resource->likers()->attach($user->id, ['created_at' => now()]);
        $this->devScore->award($resource->user, PointAction::ResourceLiked, $resource);
        $resource->refreshStats();
    }

    /**
     * Removing a like takes the 5 points back, so likes cannot be farmed.
     */
    public function unlike(User $user, Resource $resource): void
    {
        if ($resource->likers()->detach($user->id) === 0) {
            return;
        }

        $this->devScore->revoke($resource->user, PointAction::ResourceLiked, $resource);
        $resource->refreshStats();
    }

    /**
     * Rate a resource from 1 to 5 stars, optionally with a comment. Rating again updates the rating.
     */
    public function review(User $user, Resource $resource, int $rating, ?string $comment): ResourceReview
    {
        $review = $resource->reviews()->updateOrCreate(
            ['user_id' => $user->id],
            ['rating' => $rating, 'comment' => $comment],
        );

        $resource->refreshStats();

        return $review;
    }

    public function deleteReview(User $user, Resource $resource): void
    {
        $resource->reviews()->where('user_id', $user->id)->delete();
        $resource->refreshStats();
    }

    public function comment(User $user, Resource $resource, string $body): Comment
    {
        return $resource->comments()->create(['user_id' => $user->id, 'body' => $body]);
    }

    /**
     * Name of the downloaded file: "orange-money-pour-laravel.zip".
     */
    public function downloadName(Resource $resource): string
    {
        $extension = pathinfo((string) $resource->file_path, PATHINFO_EXTENSION);

        return Str::slug($resource->title).($extension ? '.'.$extension : '');
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        return $image?->store('resources/images', 'public');
    }

    /**
     * Keep the original extension (.zip, .md…) so the download opens with the right program.
     */
    private function storeFile(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $extension = Str::lower($file->getClientOriginalExtension()) ?: 'bin';

        return $file->storeAs('resources/files', Str::random(40).'.'.$extension, 'local');
    }

    private function delete(string $disk, ?string $path): void
    {
        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    private function tagIds(array $names): array
    {
        return collect($names)
            ->map(fn (string $name) => Tag::findOrCreateByName($name)->id)
            ->unique()
            ->values()
            ->all();
    }
}
