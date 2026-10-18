<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\Availability;
use App\Enums\ExperienceLevel;
use App\Enums\PointAction;
use App\Enums\ProfileType;
use App\Enums\ProjectCategory;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\ReportReason;
use App\Enums\RequestDirection;
use App\Enums\RequestStatus;
use App\Enums\ResourceType;
use App\Enums\SkillLevel;
use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Country;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Report;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\User;
use App\Services\BadgeAwarder;
use App\Services\DevScore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo community for development and the contest presentation.
 * Every demo account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    public const CITIES = [
        'GN' => ['Conakry', 'Kankan', 'Labé', 'Nzérékoré'],
        'SN' => ['Dakar', 'Thiès', 'Saint-Louis'],
        'CI' => ['Abidjan', 'Bouaké', 'Yamoussoukro'],
        'CM' => ['Douala', 'Yaoundé'],
        'ML' => ['Bamako', 'Ségou'],
        'NG' => ['Lagos', 'Abuja'],
        'GH' => ['Accra', 'Kumasi'],
        'KE' => ['Nairobi', 'Mombasa'],
        'MA' => ['Casablanca', 'Rabat'],
        'RW' => ['Kigali'],
        'BJ' => ['Cotonou', 'Porto-Novo'],
        'TG' => ['Lomé'],
        'BF' => ['Ouagadougou'],
        'ZA' => ['Johannesburg', 'Le Cap'],
        'EG' => ['Le Caire'],
        'TN' => ['Tunis'],
    ];

    private DevScore $devScore;

    /** @var Collection<string, Country> */
    private Collection $countries;

    /** @var Collection<string, Skill> */
    private Collection $skills;

    /** @var Collection<string, User> */
    private Collection $people;

    /**
     * Run the database seeds.
     */
    public function run(DevScore $devScore): void
    {
        $this->devScore = $devScore;
        $this->countries = Country::all()->keyBy('code');
        $this->skills = Skill::all()->keyBy('slug');

        $this->createAdmin();
        $this->createCoreMembers();
        $this->createCommunity(36);
        $this->createProjects();
        $this->createResources();
        $this->createFollowsAndFavorites();
        $this->awardBadges();
        $this->createConversations();
        $this->createReports();
        $this->spreadDatesOverPastMonths();
    }

    private function createAdmin(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin AfriCommit',
            'first_name' => 'Admin',
            'last_name' => 'AfriCommit',
            'username' => 'admin',
            'email' => 'admin@africommit.com',
            'country_id' => $this->countries['GN']->id,
            'city' => 'Conakry',
            'profile_type' => ProfileType::Other,
            'bio' => 'Compte administrateur de la plateforme AfriCommit.',
        ]);
    }

    /**
     * Named members used in the cahier des charges examples and in the demo scenario.
     */
    private function createCoreMembers(): void
    {
        $members = [
            ['Alpha', 'Keita', 'GN', 'Conakry', ProfileType::Developer, ExperienceLevel::Senior, Availability::Available,
                ['laravel' => 'expert', 'php' => 'expert', 'mysql' => 'advanced', 'vue' => 'intermediate'],
                'Développeur backend Laravel, passionné par les solutions de paiement et de logistique pour l\'Afrique.'],
            ['Ibrahim', 'Sow', 'SN', 'Dakar', ProfileType::Developer, ExperienceLevel::Senior, Availability::Available,
                ['flutter' => 'expert', 'dart' => 'expert', 'firebase' => 'advanced'],
                'Développeur mobile Flutter, 6 ans d\'expérience sur des apps utilisées dans toute l\'Afrique de l\'Ouest.'],
            ['Mamadou', 'Diallo', 'GN', 'Labé', ProfileType::Designer, ExperienceLevel::Intermediate, Availability::Available,
                ['ui-ux' => 'expert', 'figma' => 'expert', 'tailwind-css' => 'intermediate'],
                'Designer UI/UX. Je conçois des interfaces simples, pensées pour les connexions lentes.'],
            ['Mohamed', 'Koné', 'CI', 'Abidjan', ProfileType::Developer, ExperienceLevel::Expert, Availability::Open,
                ['react' => 'expert', 'node-js' => 'advanced', 'typescript' => 'advanced', 'mongodb' => 'intermediate'],
                'Lead développeur JavaScript à Abidjan. Mentor de la communauté React CI.'],
            ['Aminata', 'Camara', 'GN', 'Conakry', ProfileType::Developer, ExperienceLevel::Intermediate, Availability::Available,
                ['flutter' => 'advanced', 'dart' => 'advanced', 'kotlin' => 'intermediate', 'firebase' => 'intermediate'],
                'Développeuse mobile, je travaille sur des solutions e-santé pour la Guinée.'],
            ['Amadou', 'Bah', 'GN', 'Kankan', ProfileType::Developer, ExperienceLevel::Junior, Availability::Available,
                ['react-native' => 'advanced', 'javascript' => 'advanced', 'flutter' => 'beginner'],
                'Développeur mobile junior, motivé pour rejoindre des projets à impact.'],
            ['Fatoumata', 'Traoré', 'ML', 'Bamako', ProfileType::Data, ExperienceLevel::Senior, Availability::Open,
                ['python' => 'expert', 'machine-learning' => 'advanced', 'data-science' => 'advanced', 'django' => 'advanced'],
                'Data scientist. Je travaille sur le traitement automatique des langues africaines.'],
            ['Kwame', 'Mensah', 'GH', 'Accra', ProfileType::DevOps, ExperienceLevel::Senior, Availability::Busy,
                ['devops' => 'expert', 'docker' => 'expert', 'aws' => 'advanced', 'linux' => 'advanced', 'mysql' => 'advanced'],
                'Ingénieur DevOps. J\'aide les startups africaines à déployer de façon fiable et pas chère.'],
            ['Ngozi', 'Okafor', 'NG', 'Lagos', ProfileType::Developer, ExperienceLevel::Senior, Availability::Available,
                ['laravel' => 'advanced', 'php' => 'advanced', 'postgresql' => 'advanced', 'react' => 'intermediate'],
                'Full-stack developer in Lagos, building fintech products for the next billion users.'],
            ['Youssef', 'Benali', 'MA', 'Casablanca', ProfileType::Developer, ExperienceLevel::Intermediate, Availability::Open,
                ['symfony' => 'advanced', 'php' => 'advanced', 'angular' => 'intermediate', 'javascript' => 'intermediate'],
                'Développeur PHP/Symfony, contributeur open source.'],
            ['Grace', 'Mutua', 'KE', 'Nairobi', ProfileType::Product, ExperienceLevel::Senior, Availability::Available,
                ['gestion-de-projet' => 'expert', 'ui-ux' => 'intermediate'],
                'Product manager à Nairobi, ex-M-Pesa. J\'aime transformer une idée en produit.'],
            ['Aïssatou', 'Ndiaye', 'SN', 'Thiès', ProfileType::Student, ExperienceLevel::Junior, Availability::Available,
                ['html-css' => 'advanced', 'javascript' => 'intermediate', 'laravel' => 'beginner'],
                'Étudiante en informatique, je cherche un premier projet pour progresser en Laravel.'],
        ];

        $this->people = collect();

        foreach ($members as [$first, $last, $code, $city, $type, $level, $availability, $skills, $bio]) {
            $user = User::factory()->create([
                'name' => "$first $last",
                'first_name' => $first,
                'last_name' => $last,
                'email' => Str::slug("$first $last", '.').'@africommit.com',
                'country_id' => $this->countries[$code]->id,
                'city' => $city,
                'profile_type' => $type,
                'experience_level' => $level,
                'availability' => $availability,
                'bio' => $bio,
                'linkedin_url' => 'https://www.linkedin.com/in/'.Str::slug("$first $last"),
                'portfolio_url' => 'https://'.Str::slug("$first $last").'.dev',
            ]);

            $user->skills()->attach(
                collect($skills)->mapWithKeys(fn (string $level, string $slug) => [
                    $this->skills[$slug]->id => ['level' => $level],
                ])->all()
            );

            $this->completeProfile($user);
            $this->people[$first] = $user;
        }
    }

    private function createCommunity(int $count): void
    {
        User::factory($count)->create()->each(function (User $user) {
            $code = array_rand(self::CITIES);

            $user->update([
                'country_id' => $this->countries[$code]->id,
                'city' => fake()->randomElement(self::CITIES[$code]),
            ]);

            $user->skills()->attach(
                $this->skills->random(fake()->numberBetween(2, 4))->mapWithKeys(fn (Skill $skill) => [
                    $skill->id => ['level' => fake()->randomElement(SkillLevel::cases())->value],
                ])->all()
            );

            $this->completeProfile($user);
        });
    }

    private function createProjects(): void
    {
        $p = $this->people;

        $africaDelivery = $this->createProject($p['Alpha'], [
            'title' => 'AfricaDelivery',
            'description' => 'Application de livraison destinée aux commerces africains : prise de commande, suivi des livreurs en temps réel et paiement par mobile money.',
            'category' => ProjectCategory::Logistics, 'country' => 'GN', 'status' => ProjectStatus::Recruiting, 'members_wanted' => 3,
        ], ['laravel', 'flutter', 'ui-ux', 'mysql'], [
            ['Laravel Developer', 'laravel', 1, null],
            ['Flutter Developer', 'flutter', 1, $p['Ibrahim']],
            ['UI/UX Designer', 'ui-ux', 1, $p['Mamadou']],
        ]);

        $this->createProject($p['Ngozi'], [
            'title' => 'FinTech Africa',
            'description' => 'Portefeuille numérique interopérable entre les opérateurs de mobile money d\'Afrique de l\'Ouest, avec transferts instantanés et sans frais cachés.',
            'category' => ProjectCategory::Fintech, 'country' => 'NG', 'status' => ProjectStatus::InProgress, 'members_wanted' => 3,
        ], ['laravel', 'react', 'postgresql'], [
            ['Backend Laravel', 'laravel', 1, $p['Alpha']],
            ['Frontend React', 'react', 1, $p['Mohamed']],
            ['DevOps', 'devops', 1, $p['Kwame']],
        ]);

        $healthTech = $this->createProject($p['Aminata'], [
            'title' => 'HealthTech Guinea',
            'description' => 'Prise de rendez-vous médicaux et dossier patient simplifié pour les centres de santé de Guinée, utilisable même hors connexion.',
            'category' => ProjectCategory::Healthtech, 'country' => 'GN', 'status' => ProjectStatus::Recruiting, 'members_wanted' => 3,
        ], ['flutter', 'firebase', 'ui-ux'], [
            ['Flutter Developer', 'flutter', 1, null],
            ['UI/UX Designer', 'ui-ux', 1, $p['Mamadou']],
            ['Backend Node.js', 'node-js', 1, null],
        ]);

        $agriConnect = $this->createProject($p['Mohamed'], [
            'title' => 'AgriConnect',
            'description' => 'Plateforme qui met en relation agriculteurs et acheteurs, avec les prix du marché envoyés par SMS.',
            'category' => ProjectCategory::Agritech, 'country' => 'CI', 'status' => ProjectStatus::Idea, 'members_wanted' => 2,
        ], ['react', 'node-js', 'mongodb'], [
            ['Développeur Node.js', 'node-js', 1, null],
            ['Data Scientist', 'data-science', 1, null],
        ]);

        $this->createProject($p['Fatoumata'], [
            'title' => 'EduLink Sahel',
            'description' => 'Cours hors ligne et quiz adaptatifs pour les lycéens du Sahel, synchronisés dès que le réseau revient.',
            'category' => ProjectCategory::Edtech, 'country' => 'ML', 'status' => ProjectStatus::Completed, 'members_wanted' => 2,
        ], ['python', 'django', 'react'], [
            ['Développeur Django', 'django', 1, $p['Youssef']],
            ['Product Manager', 'gestion-de-projet', 1, $p['Grace']],
        ]);

        // Pending and answered collaboration requests for the demo.
        $africaDelivery->collaborationRequests()->create([
            'user_id' => $p['Ngozi']->id,
            'direction' => RequestDirection::Application,
            'project_role_id' => $africaDelivery->roles()->where('title', 'Laravel Developer')->value('id'),
            'message' => 'Bonjour, j\'aimerais rejoindre ton projet en tant que développeuse Laravel.',
            'status' => RequestStatus::Pending,
        ]);

        $healthTech->collaborationRequests()->create([
            'user_id' => $p['Amadou']->id,
            'direction' => RequestDirection::Application,
            'project_role_id' => $healthTech->roles()->where('title', 'Flutter Developer')->value('id'),
            'message' => 'Bonjour Aminata, je débute en Flutter mais je suis très motivé par la e-santé.',
            'status' => RequestStatus::Pending,
        ]);

        $healthTech->collaborationRequests()->create([
            'user_id' => $p['Ibrahim']->id,
            'direction' => RequestDirection::Invitation,
            'project_role_id' => $healthTech->roles()->where('title', 'Flutter Developer')->value('id'),
            'message' => 'Salut Ibrahim, ton profil Flutter correspond parfaitement à HealthTech Guinea. Ça te dit ?',
            'status' => RequestStatus::Pending,
        ]);

        $agriConnect->collaborationRequests()->create([
            'user_id' => $p['Aïssatou']->id,
            'direction' => RequestDirection::Application,
            'message' => 'Je voudrais apprendre en participant au projet.',
            'status' => RequestStatus::Rejected,
            'responded_at' => now(),
        ]);

        // A few more community projects.
        User::query()->whereNotIn('id', $this->people->pluck('id'))->where('is_admin', false)
            ->inRandomOrder()->limit(6)->get()
            ->each(function (User $owner) {
                $skills = $this->skills->random(3);

                $this->createProject($owner, [
                    'title' => fake()->randomElement(['Smart', 'Open', 'Sahel', 'Baobab', 'Ubuntu', 'Teranga']).' '.fake()->randomElement(['Market', 'Pay', 'Health', 'School', 'Transport', 'Farm']),
                    'description' => fake()->paragraph(3),
                    'category' => fake()->randomElement(ProjectCategory::cases()),
                    'country' => $this->countries->firstWhere('id', $owner->country_id)->code,
                    'status' => ProjectStatus::Recruiting,
                    'members_wanted' => 2,
                ], $skills->pluck('slug')->all(), $skills->take(2)->map(fn (Skill $skill) => [$skill->name.' Developer', $skill->slug, 1, null])->all());
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $skillSlugs
     * @param  list<array{0: string, 1: string, 2: int, 3: ?User}>  $roles  [title, skill slug, slots, member already in the role]
     */
    private function createProject(User $owner, array $data, array $skillSlugs, array $roles): Project
    {
        $project = Project::create([
            'owner_id' => $owner->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'country_id' => $this->countries[$data['country']]->id,
            'status' => $data['status'],
            'members_wanted' => $data['members_wanted'],
        ]);

        $project->skills()->attach(collect($skillSlugs)->map(fn (string $slug) => $this->skills[$slug]->id));

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => ProjectMemberRole::Owner,
            'joined_at' => now(),
        ]);
        $this->devScore->award($owner, PointAction::ProjectCreated, $project);
        $this->logActivity($owner, ActivityType::ProjectCreated, $project);

        foreach ($roles as [$title, $skillSlug, $slots, $member]) {
            $role = $project->roles()->create([
                'title' => $title,
                'skill_id' => $this->skills[$skillSlug]->id,
                'slots' => $slots,
            ]);

            if ($member) {
                ProjectMember::create([
                    'project_id' => $project->id,
                    'user_id' => $member->id,
                    'project_role_id' => $role->id,
                    'role' => ProjectMemberRole::Member,
                    'joined_at' => now(),
                ]);
                $this->devScore->award($member, PointAction::Collaboration, $project);
                $this->logActivity($member, ActivityType::ProjectJoined, $project);
            }
        }

        if ($data['status'] === ProjectStatus::Completed) {
            $project->forceFill(['completed_at' => now()])->save();
            $this->devScore->award($owner, PointAction::ProjectCompleted, $project);
        }

        return $project;
    }

    private function createResources(): void
    {
        $p = $this->people;

        $resources = [
            [$p['Alpha'], 'Orange Money pour Laravel', ResourceType::Package, ['laravel', 'php'], ['paiement', 'mobile money', 'orange money'],
                'Package Laravel pour encaisser des paiements Orange Money (Guinée, Sénégal, Côte d\'Ivoire, Mali) en quelques lignes : initialisation, webhooks et vérification des transactions.'],
            [$p['Ibrahim'], 'Wave API Client', ResourceType::Api, ['flutter', 'dart'], ['paiement', 'mobile money', 'wave'],
                'Client Dart pour l\'API Wave : paiements, remboursements et suivi des transactions depuis une app Flutter.'],
            [$p['Aminata'], 'Flutter Delivery UI Kit', ResourceType::UiKit, ['flutter', 'dart'], ['livraison', 'ui', 'mobile'],
                '30 écrans Flutter prêts à l\'emploi pour une app de livraison : suivi de commande, carte du livreur, paiement.'],
            [$p['Ngozi'], 'Laravel SaaS Starter Afrique', ResourceType::Template, ['laravel', 'tailwind-css', 'postgresql'], ['saas', 'multi-tenant', 'paiement'],
                'Template SaaS Laravel avec abonnements en mobile money, multi-entreprises et facturation en XOF, NGN et KES.'],
            [$p['Youssef'], 'Africa Geo Data', ResourceType::Library, ['javascript', 'php'], ['géographie', 'données', 'pays'],
                'Bibliothèque JS et PHP avec les 54 pays africains, leurs régions, villes, indicatifs et monnaies.'],
            [$p['Mamadou'], 'Kit Figma AfriCommit', ResourceType::UiKit, ['figma', 'ui-ux'], ['design system', 'figma'],
                'Design system Figma : couleurs inspirées des tissus africains, composants, icônes et maquettes mobile.'],
            [$p['Kwame'], 'Script de sauvegarde MySQL', ResourceType::Script, ['mysql', 'linux'], ['backup', 'devops'],
                'Script bash de sauvegarde automatique MySQL vers un stockage S3, avec rotation et alerte par email.'],
            [$p['Kwame'], 'Déployer Laravel sur un VPS', ResourceType::Documentation, ['laravel', 'linux', 'docker'], ['déploiement', 'devops'],
                'Guide pas à pas pour mettre une app Laravel en production : Nginx, HTTPS, files d\'attente, sauvegardes.'],
            [$p['Fatoumata'], 'AfriNLP — modèles wolof et pular', ResourceType::OpenSource, ['python', 'machine-learning'], ['ia', 'langues africaines'],
                'Modèles open source de traduction et de reconnaissance d\'intentions pour le wolof et le pular.'],
            [$p['Mohamed'], 'React Mobile Money Checkout', ResourceType::Package, ['react', 'typescript'], ['paiement', 'mobile money'],
                'Composant React de paiement compatible Orange Money, MTN MoMo, Wave et Moov.'],
            [$p['Youssef'], 'Mini-framework API REST PHP', ResourceType::Framework, ['php'], ['api', 'rest'],
                'Framework PHP léger pour créer des API REST rapides sur des hébergements mutualisés.'],
        ];

        foreach ($resources as [$author, $title, $type, $skillSlugs, $tags, $description]) {
            $resource = Resource::create([
                'user_id' => $author->id,
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'country_id' => $author->country_id,
                'github_url' => 'https://github.com/'.Str::slug($author->name).'/'.Str::slug($title),
                'demo_url' => fake()->boolean(40) ? 'https://demo.'.Str::slug($title).'.dev' : null,
            ]);
            $resource->forceFill(['downloads_count' => fake()->numberBetween(10, 400)])->save();

            $resource->skills()->attach(collect($skillSlugs)->map(fn (string $slug) => $this->skills[$slug]->id));
            $resource->tags()->attach(collect($tags)->map(fn (string $tag) => Tag::findOrCreateByName($tag)->id));

            $this->devScore->award($author, PointAction::ResourcePublished, $resource);
            $this->logActivity($author, ActivityType::ResourcePublished, $resource);
            $this->addInteractions($resource);
        }

        // One resource waiting for admin validation.
        Resource::factory()->pending()->create([
            'user_id' => User::query()->where('is_admin', false)->inRandomOrder()->value('id'),
            'title' => 'Template de boutique en ligne',
        ]);
    }

    private function addInteractions(Resource $resource): void
    {
        $others = User::query()->where('id', '!=', $resource->user_id)->where('is_admin', false)->inRandomOrder()->limit(12)->get();
        $reviewComments = [
            'Très bonne ressource, je l\'utilise en production.',
            'Simple à intégrer, la documentation est claire.',
            'Exactement ce qu\'il me fallait pour mon projet.',
            'Bon travail, il manque juste quelques exemples.',
            null,
        ];

        foreach ($others->take(fake()->numberBetween(2, 6)) as $reviewer) {
            $resource->reviews()->create([
                'user_id' => $reviewer->id,
                'rating' => fake()->numberBetween(3, 5),
                'comment' => fake()->randomElement($reviewComments),
            ]);
        }

        foreach ($others->shuffle()->take(fake()->numberBetween(3, 10)) as $liker) {
            $resource->likers()->attach($liker->id, ['created_at' => now()]);
            $this->devScore->award($resource->user, PointAction::ResourceLiked, $resource);
        }

        foreach ($others->shuffle()->take(fake()->numberBetween(0, 3)) as $commenter) {
            $resource->comments()->create([
                'user_id' => $commenter->id,
                'body' => fake()->randomElement([
                    'Merci pour le partage !',
                    'Est-ce que ça marche aussi avec Laravel 13 ?',
                    'Super utile, je l\'ai ajouté à mes favoris.',
                    'Une version pour le Cameroun est prévue ?',
                ]),
            ]);
        }

        $resource->refreshStats();
    }

    private function createFollowsAndFavorites(): void
    {
        $users = User::query()->where('is_admin', false)->get();
        $resources = Resource::query()->published()->get();
        $projects = Project::all();

        foreach ($users as $user) {
            $others = $users->where('id', '!=', $user->id);

            $user->following()->attach($others->random(fake()->numberBetween(3, 8))->pluck('id'));

            foreach ($resources->random(2) as $resource) {
                $user->favorites()->create(['favoritable_type' => $resource->getMorphClass(), 'favoritable_id' => $resource->id]);
            }

            $project = $projects->where('owner_id', '!=', $user->id)->random();
            $user->favorites()->create(['favoritable_type' => $project->getMorphClass(), 'favoritable_id' => $project->id]);
        }
    }

    /**
     * Most badges are already awarded along the way (DevScore::award checks them).
     * A final pass catches the ones that depend on the whole team, like "Panafricain".
     */
    private function awardBadges(): void
    {
        $awarder = app(BadgeAwarder::class);

        User::query()->where('is_admin', false)->each(fn (User $user) => $awarder->evaluate($user));
    }

    private function createConversations(): void
    {
        $p = $this->people;

        $threads = [
            [$p['Ibrahim'], $p['Alpha'], [
                [$p['Ibrahim'], 'Salut Alpha, j\'ai vu AfricaDelivery, le projet m\'intéresse beaucoup !'],
                [$p['Alpha'], 'Merci Ibrahim ! On a justement besoin d\'un expert Flutter. Bienvenue dans l\'équipe 🎉'],
                [$p['Ibrahim'], 'Top. Je commence par l\'écran de suivi du livreur ?'],
                [$p['Alpha'], 'Parfait, je prépare l\'API de géolocalisation de mon côté.'],
            ]],
            [$p['Aminata'], $p['Mamadou'], [
                [$p['Aminata'], 'Mamadou, tu peux regarder les maquettes de HealthTech Guinea ?'],
                [$p['Mamadou'], 'Oui, je t\'envoie une première version demain.'],
            ]],
        ];

        foreach ($threads as [$a, $b, $messages]) {
            $conversation = Conversation::between($a, $b);

            foreach ($messages as $index => [$sender, $body]) {
                $isLast = $index === array_key_last($messages);
                $conversation->messages()->create([
                    'sender_id' => $sender->id,
                    'body' => $body,
                    'read_at' => $isLast ? null : now(),
                ]);
            }

            $conversation->update(['last_message_at' => now()]);
        }
    }

    private function createReports(): void
    {
        $reporter = $this->people['Grace'];

        Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => 'resource',
            'reportable_id' => Resource::query()->where('status', 'pending')->value('id'),
            'reason' => ReportReason::Spam,
            'details' => 'Le lien GitHub ne mène nulle part.',
        ]);
    }

    /**
     * Spread points and activities over the last 6 months so the monthly and yearly rankings have data.
     */
    private function spreadDatesOverPastMonths(): void
    {
        foreach ([PointTransaction::class, Activity::class] as $model) {
            $table = (new $model)->getTable();

            DB::table($table)->orderBy('id')->pluck('id')->each(function (int $id) use ($table) {
                DB::table($table)->where('id', $id)->update(['created_at' => now()->subDays(fake()->numberBetween(0, 180))]);
            });
        }
    }

    private function completeProfile(User $user): void
    {
        $user->forceFill(['profile_completed_at' => now()])->save();
        $this->devScore->award($user, PointAction::ProfileCompleted, $user);
    }

    private function logActivity(User $user, ActivityType $type, Model $subject): void
    {
        Activity::create([
            'user_id' => $user->id,
            'type' => $type,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }
}
