/** Canonical brand assets and contact details — Welcome 2 Kigali Expats Club */

export const COMPANY_NAME =
  import.meta.env.VITE_COMPANY_NAME || 'Welcome 2 Kigali Expats Club';

export const COMPANY_NAME_SHORT =
  import.meta.env.VITE_COMPANY_NAME_SHORT || 'Welcome 2 Kigali';

export const TAGLINE = 'LIVE. CONNECT. THRIVE.';
export const TAGLINE_RWANDA = 'EXPERIENCE RWANDA';
export const TAGLINE_FOOTER = 'A DESTINATION. A COMMUNITY. AN EXPERIENCE.';
export const TAGLINE_BELONG = 'EXPERIENCE RWANDA. BELONG IN KIGALI.';

export const WHATSAPP_PHONE =
  import.meta.env.VITE_ADMIN_PHONE_NUMBER || '';

/** Digits only — for wa.me links */
export const WHATSAPP_WA_ME = WHATSAPP_PHONE.replace(/\D/g, '');

export const CONTACT_PHONE_DISPLAY =
  import.meta.env.VITE_CONTACT_PHONE_DISPLAY || '';

export const WEBSITE_URL =
  import.meta.env.VITE_SITE_URL || 'https://welcome2kigali.com';

export const WEBSITE_HOST =
  import.meta.env.VITE_WEBSITE_HOST || 'www.welcome2kigali.com';

export const CONTACT_EMAIL =
  import.meta.env.VITE_CONTACT_EMAIL || 'hello@welcome2kigali.com';

export const DEFAULT_LOGO_URL =
  import.meta.env.VITE_LOGO_URL || '/branding/w2k-logo.png';

export const DEFAULT_MARK_URL = '/branding/w2k-mark.png';

export const HERO_IMAGE_URL =
  import.meta.env.VITE_HERO_IMAGE_URL || '/branding/w2k-logo.png';

export const BRAND_GOLD = '#C5A059';
export const BRAND_GOLD_BRIGHT = '#D4AF37';
export const BRAND_BLACK = '#0A0A0A';
export const BRAND_CREAM = '#F7F1E8';

/** Software developer credit — shown in footer/login. Phone links to WhatsApp. */
export const DEVELOPER = {
  name: 'Sr. Engr. Tefu R. Mbole',
  phone: '+237675321739',
  waMe: '237675321739',
  whatsAppUrl: 'https://wa.me/237675321739',
};

export function whatsAppUrl(message) {
  if (!WHATSAPP_WA_ME) return '';
  const text = typeof message === 'string' ? message : '';
  return `https://wa.me/${WHATSAPP_WA_ME}?text=${encodeURIComponent(text)}`;
}

export function isValidLogoUrl(url) {
  if (!url || typeof url !== 'string') return false;
  return /^https?:\/\/.+/i.test(url.trim()) || url.startsWith('/');
}
