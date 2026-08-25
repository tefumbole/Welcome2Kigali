import { Router } from 'express';
import { getPool } from '../db/pool.js';

const router = Router();

const CATEGORY_ORDER = [
  'Coffee & Tea',
  'Iced & Specialty',
  'Tea & Hot Beverages',
  'Fresh & Detox Juices',
  'Smoothies',
  'Food',
];

router.get('/public/menu', async (_req, res) => {
  try {
    const pool = getPool();
    const [tables] = await pool.query(
      `SELECT TABLE_NAME FROM information_schema.TABLES
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('categories', 'products')`
    );
    if (!tables || tables.length < 2) {
      return res.json({ groups: [] });
    }

    const [categories] = await pool.query(
      `SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name`
    );
    const ordered = [...categories].sort((a, b) => {
      const ai = CATEGORY_ORDER.indexOf(a.name);
      const bi = CATEGORY_ORDER.indexOf(b.name);
      return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
    });

    const groups = [];
    for (const category of ordered) {
      const [items] = await pool.query(
        `SELECT id, name, product_details AS details, price
         FROM products WHERE category_id = ? AND is_active = 1 ORDER BY name`,
        [category.id]
      );
      groups.push({
        id: category.id,
        name: category.name,
        items: items.map((item) => ({
          id: item.id,
          name: item.name,
          details: item.details,
          price: Number(item.price) || 0,
        })),
      });
    }

    res.json({ groups });
  } catch (err) {
    console.error('public menu:', err.message);
    res.json({ groups: [] });
  }
});

export default router;
