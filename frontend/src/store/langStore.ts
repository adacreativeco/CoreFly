import { create } from 'zustand';
import { tr } from '@/locales/tr';
import { en } from '@/locales/en';

export type Language = 'tr' | 'en';

interface LangState {
  lang: Language;
  setLang: (lang: Language) => void;
  toggleLang: () => void;
  t: (key: string, params?: Record<string, string | number>) => string;
}

const translations = {
  tr,
  en,
};

const getInitialLang = (): Language => {
  const saved = localStorage.getItem('corefly_lang');
  if (saved === 'tr' || saved === 'en') return saved;
  const browser = navigator.language?.toLowerCase();
  return browser.startsWith('tr') ? 'tr' : 'en';
};

export const useLangStore = create<LangState>((set, get) => ({
  lang: getInitialLang(),
  setLang: (lang: Language) => {
    localStorage.setItem('corefly_lang', lang);
    set({ lang });
  },
  toggleLang: () => {
    const next = get().lang === 'tr' ? 'en' : 'tr';
    localStorage.setItem('corefly_lang', next);
    set({ lang: next });
  },
  t: (key: string, params?: Record<string, string | number>) => {
    const { lang } = get();
    const dict = translations[lang] || translations.tr;
    let text = (dict as Record<string, string>)[key] || (translations.tr as Record<string, string>)[key] || key;
    if (params) {
      Object.keys(params).forEach((p) => {
        text = text.replace(`{${p}}`, String(params[p]));
      });
    }
    return text;
  },
}));
