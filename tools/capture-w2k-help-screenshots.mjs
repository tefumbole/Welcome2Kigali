#!/usr/bin/env node
/**
 * Capture live Welcome 2 Kigali screens into laravel-app/public/branding/help/.
 * Public pages: production. Admin pages: local artisan serve (already logged in).
 */
import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.resolve(__dirname, '../laravel-app/public/branding/help');
const LIVE = process.env.W2K_LIVE_URL || 'https://welcome2kigali.net';
const LOCAL = process.env.W2K_LOCAL_URL || 'http://127.0.0.1:8765';
const USER = process.env.W2K_SHOT_USER || 'admin';
const PASS = process.env.W2K_SHOT_PASS || '';

fs.mkdirSync(OUT, { recursive: true });

async function shot(page, file, opts = {}) {
  const dest = path.join(OUT, file);
  await page.waitForTimeout(opts.wait || 600);
  await page.screenshot({
    path: dest,
    fullPage: opts.fullPage !== false,
    animations: 'disabled',
  });
  console.log('wrote', dest);
}

async function hideChromeNoise(page) {
  await page.addStyleTag({
    content: `
      #loader { display: none !important; }
      #content { display: block !important; }
      .w2k-wa-fab { display: none !important; }
    `,
  });
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1440, height: 900 },
  deviceScaleFactor: 1,
});
const page = await context.newPage();
page.setDefaultTimeout(45000);

const publicPages = [
  { url: `${LIVE}/`, file: 'home.png', fullPage: false },
  { url: `${LIVE}/login`, file: 'login.png', fullPage: false },
  { url: `${LIVE}/about`, file: 'about.png', fullPage: true },
  { url: `${LIVE}/events`, file: 'events.png', fullPage: true },
  { url: `${LIVE}/menu`, file: 'menu.png', fullPage: false },
    { url: `${LIVE}/register-now`, file: 'register.png', fullPage: true },
  { url: `${LIVE}/apply-now`, file: 'apply-now.png', fullPage: false },
  { url: `${LIVE}/gallery`, file: 'gallery.png', fullPage: false },
  { url: `${LIVE}/contact`, file: 'contact.png', fullPage: false },
];

for (const item of publicPages) {
  await page.goto(item.url, { waitUntil: 'networkidle' });
  await hideChromeNoise(page);
  if (item.file === 'login.png') {
    await page.addStyleTag({
      content: `
        .beyond-logo-spin-wrap {
          background: #ffffff !important;
        }
        .beyond-logo-spin {
          background: #ffffff !important;
        }
      `,
    });
  }
  await shot(page, item.file, { fullPage: item.fullPage, wait: 800 });
}

if (PASS) {
  await page.goto(`${LOCAL}/login`, { waitUntil: 'networkidle' });
  await page.fill('input[name="identifier"]', USER);
  await page.fill('input[name="password"]', PASS);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
  await page.waitForTimeout(1200);

  const adminPages = [
    { url: `${LOCAL}/admin`, file: 'sidebar-help.png', fullPage: false, scrollHelp: true },
    { url: `${LOCAL}/products`, file: 'products-list.png', fullPage: false },
    { url: `${LOCAL}/products/create`, file: 'add-product.png', fullPage: false },
    { url: `${LOCAL}/pos`, file: 'pos.png', fullPage: false },
    { url: `${LOCAL}/bookings/create`, file: 'rental.png', fullPage: false },
    { url: `${LOCAL}/bookings`, file: 'tabs-help.png', fullPage: false },
    { url: `${LOCAL}/quotations`, file: 'quotation.png', fullPage: false },
    { url: `${LOCAL}/expenses`, file: 'expenses.png', fullPage: false },
    { url: `${LOCAL}/purchases`, file: 'purchase.png', fullPage: false },
    { url: `${LOCAL}/admin/membership`, file: 'membership.png', fullPage: false },
    { url: `${LOCAL}/admin/site-content`, file: 'site-content.png', fullPage: false },
    { url: `${LOCAL}/payment/index`, file: 'payments.png', fullPage: false },
    { url: `${LOCAL}/admin/laundry/ready`, file: 'laundry.png', fullPage: false },
    { url: `${LOCAL}/admin/site-content?tab=pos-payments`, file: 'site-content-pos-payments.png', fullPage: false },
  ];

  for (const item of adminPages) {
    await page.goto(item.url, { waitUntil: 'networkidle' });
    await hideChromeNoise(page);
    await page.waitForTimeout(700);
    if (item.scrollHelp) {
      await page.evaluate(() => {
        const scroller = document.querySelector('.side-navbar .main-menu');
        const help = document.querySelector('#help-module, a[href*="/admin/help"]');
        if (scroller && help) {
          const row = help.closest('#side-main-menu > li') || help;
          row.scrollIntoView({ block: 'center' });
        }
      });
      await page.waitForTimeout(300);
    }
    await shot(page, item.file, { fullPage: false, wait: 400 });
  }
} else {
  console.log('skip admin shots (no W2K_SHOT_PASS)');
}

await browser.close();
