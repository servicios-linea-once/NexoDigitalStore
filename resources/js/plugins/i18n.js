import { createI18n } from 'vue-i18n'
import { useLocalStorage } from '@vueuse/core'

// ── Locale files ──────────────────────────────────────────────────────────────
import es from '../locales/es'
import en from '../locales/en'

// Detect saved locale or use browser preference, fallback to 'es'
const savedLocale = useLocalStorage('nexo-locale', null)
const browserLang = navigator.language?.slice(0, 2) ?? 'es'
const defaultLocale = savedLocale.value ?? (['es', 'en'].includes(browserLang) ? browserLang : 'es')

export const i18n = createI18n({
    legacy: false,          // Composition API mode
    locale: defaultLocale,
    fallbackLocale: 'es',
    messages: { es, en },
    missingWarn: false,
    fallbackWarn: false,
})

/** Change locale at runtime */
export function setLocale(lang) {
    if (['es', 'en'].includes(lang)) {
        i18n.global.locale.value = lang
        savedLocale.value = lang
        document.documentElement.setAttribute('lang', lang)
    }
}

export default i18n
