// resources/js/i18n.js
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import Backend from 'i18next-http-backend';
import LanguageDetector from 'i18next-browser-languagedetector';

// Make i18n instance available for direct imports
export { default as i18next } from 'i18next';

// Custom backend to handle the modified response format
// const customBackend = {
//   type: 'backend',
//   init: function(services, backendOptions) {
//     this.services = services;
//     this.options = backendOptions;
//   },
//   read: function(language, namespace, callback) {
//     const loadPath = window.route ? window.route('translations', language) : `/translations/${language}`;

//     fetch(loadPath)
//       .then(response => response.json())
//       .then(data => {
//         // Extract translations from the structured response
//         const translations = data.translations;

//         // Set document direction based on layoutDirection
//         if (data.layoutDirection) {
//           // Always keep LTR for sidebar compatibility
//           document.documentElement.dir = 'ltr';
//           document.documentElement.setAttribute('dir', 'ltr');
//         }

//         // Store the current locale
//         if (data.locale) {
//           localStorage.setItem('i18nextLng', data.locale);
//           // Also store in a session cookie for server-side awareness
//           document.cookie = `app_language=${data.locale}; path=/; max-age=${60 * 60 * 24}`;
//         }

//         callback(null, translations);
//       })
//       .catch(error => {
//         console.error('Translation loading error:', error);
//         callback(error, null);
//       });
//   }
// };

const customBackend = {
  type: 'backend',
  init: function (services, backendOptions) {
    this.services = services;
    this.options = backendOptions;
  },
  read: function (language, namespace, callback) {
    const loadPath = window.route ? window.route('translations', language) : `/translations/${language}`;

    fetch(loadPath)
      .then(response => response.json())
      .then(data => {
        // Extract translations from the structured response
        const translations = data.translations;

        // Set document direction - always keep LTR for sidebar compatibility
        document.documentElement.dir = 'ltr';
        document.documentElement.setAttribute('dir', 'ltr');

        callback(null, translations);
      })
      .catch(error => {
        console.error('Translation loading error:', error);
        callback(error, null);
      });
  }
};

// Function to get initial language
const getInitialLanguage = () => {
  // Try to get from server if available

  // if (window.initialLocale) {
  //   return window.initialLocale;
  // }
  return null;
};

// Function to reset language cache when switching languages
const resetLanguageCache = (language) => {
  // Clear any cached translations for better switching
  if (window.localStorage) {
    Object.keys(localStorage).forEach(key => {
      if (key.startsWith('i18next_res_')) {
        localStorage.removeItem(key);
      }
    });
  }
};

// Override the changeLanguage method to reset cache
const originalChangeLanguage = i18n.changeLanguage;
i18n.changeLanguage = function (language) {
  resetLanguageCache(language);
  return originalChangeLanguage.apply(this, arguments);
};

// Initialize i18n
// i18n
//   .use(customBackend)
//   .use(LanguageDetector)
//   .use(initReactI18next)
//   .init({
//     lng: getInitialLanguage(),
//     fallbackLng: getInitialLanguage(),
//     load: 'currentOnly',
//     debug: process.env.NODE_ENV === 'development',

//     interpolation: {
//       escapeValue: false,
//     },

//     detection: {
//       order: ['localStorage', 'cookie', 'navigator'],
//       lookupCookie: 'app_language',
//       caches: ['localStorage', 'cookie'],
//     },

//     ns: ['translation'],
//     defaultNS: 'translation',

//     partialBundledLanguages: true,
//     loadOnInitialization: true
//   });

// Initialize i18n
i18n
  .use(customBackend)
  .use(initReactI18next)
  .init({
    lng: undefined,
    fallbackLng: false,
    load: 'currentOnly',
    debug: process.env.NODE_ENV === 'development',

    interpolation: {
      escapeValue: false,
    },

    ns: ['translation'],
    defaultNS: 'translation',

    partialBundledLanguages: true,
    loadOnInitialization: false
  });

// Export the initialized instance
export default i18n;

// Make sure the i18n instance is available for direct imports
window.i18next = i18n;
