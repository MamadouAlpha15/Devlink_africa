import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * AfriCommit — jetons de design.
 *
 * Principes tenus dans tout le produit :
 * - aucun dégradé : des aplats, des bordures fines, très peu d'ombre ;
 * - le neutre porte l'interface, l'indigo ne marque que l'action principale
 *   et la fonctionnalité IA — jamais la décoration ;
 * - trois rayons seulement, corrélés à la taille de la boîte (6 / 8 / 12) ;
 * - deux ombres seulement : une pour poser une surface, une pour la faire
 *   flotter au-dessus du plan.
 *
 * Le mode sombre est piloté par la classe `dark` sur <html>.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                // Action principale et fonctionnalité IA. Usage volontairement rare.
                brand: {
                    50: '#EEF2FF',
                    100: '#E0E7FF',
                    200: '#C7D2FE',
                    300: '#A5B4FC',
                    400: '#818CF8',
                    500: '#6366F1',
                    600: '#4F46E5',
                    700: '#4338CA',
                    800: '#3730A3',
                    900: '#312E81',
                    950: '#1E1B4B',
                },
                // Neutres : ils portent 95 % de l'interface.
                ink: {
                    50: '#F8FAFC',
                    100: '#F1F5F9',
                    200: '#E6EAF0',
                    300: '#CBD5E1',
                    400: '#94A3B8',
                    500: '#64748B',
                    600: '#475569',
                    700: '#334155',
                    800: '#1E293B',
                    900: '#0F172A',
                    950: '#0B1120',
                },
            },

            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },

            boxShadow: {
                // Poser une surface sur le fond, sans la décoller.
                card: '0 1px 2px 0 rgb(15 23 42 / 0.05)',
                // Faire flotter : menus, modales. Une seule profondeur pour les deux.
                overlay: '0 16px 40px -12px rgb(15 23 42 / 0.18), 0 4px 8px -4px rgb(15 23 42 / 0.06)',
            },

            keyframes: {
                // Barre indéterminée du chargement AfriCommit AI : la seule
                // animation en boucle du produit, et elle signale un travail
                // réellement en cours.
                sweep: {
                    '0%': { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(300%)' },
                },
            },

            animation: {
                sweep: 'sweep 1.4s cubic-bezier(0.4, 0, 0.2, 1) infinite',
            },

            transitionTimingFunction: {
                snap: 'cubic-bezier(0.32, 0.72, 0, 1)',
            },
        },
    },

    plugins: [forms],
};
