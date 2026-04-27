/**
 * Nexo Theme System
 * Reads initial values from Inertia shared props (DB for auth users).
 * Persists to DB via PATCH /ui/preferences + localStorage fallback for guests.
 */

import { useLocalStorage } from '@vueuse/core'
import { usePage, router } from '@inertiajs/vue3'
import { watch, ref } from 'vue'

export const THEMES = [
    { id: 'nexo',     label: 'Nexo',     dot: 'theme-dot-nexo'     },
    { id: 'midnight', label: 'Midnight', dot: 'theme-dot-midnight' },
    { id: 'ocean',    label: 'Ocean',    dot: 'theme-dot-ocean'    },
    { id: 'ember',    label: 'Ember',    dot: 'theme-dot-ember'    },
]

// Composable — call once at app root, reuse across components via provide/inject
// or re-call (each instance reads same localStorage keys)
export function useTheme() {
    const page = usePage()

    // Initial values: DB (via Inertia) → localStorage → default
    const serverUi = page.props?.ui
    const theme = useLocalStorage('nexo-theme', serverUi?.theme ?? 'nexo')
    const mode  = useLocalStorage('nexo-mode',  serverUi?.mode  ?? 'dark')

    function applyToDOM() {
        const el = document.documentElement
        el.setAttribute('data-theme',      theme.value)
        el.setAttribute('data-theme-mode', mode.value)
    }

    async function persistToServer(payload) {
        const user = page.props?.auth?.user
        if (!user) return // guest — localStorage only
        try {
            await fetch('/ui/preferences', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
        } catch { /* silent fail */ }
    }

    function setTheme(name) {
        theme.value = name
        applyToDOM()
        persistToServer({ theme: name })
    }

    function setMode(m) {
        mode.value = m
        applyToDOM()
        persistToServer({ mode: m })
    }

    function toggleMode() {
        setMode(mode.value === 'dark' ? 'light' : 'dark')
    }

    function setLocale(lang) {
        persistToServer({ locale: lang })
    }

    // Apply immediately on call
    applyToDOM()

    // Re-apply if Inertia navigates (user logs in/out with different prefs)
    watch(() => page.props?.ui, (ui) => {
        if (!ui) return
        theme.value = ui.theme
        mode.value  = ui.mode
        applyToDOM()
    }, { deep: true })

    return { theme, mode, setTheme, setMode, toggleMode, setLocale, THEMES }
}
