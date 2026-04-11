#!/usr/bin/env node
/**
 * Converts phpMyAdmin MySQL export (JSON) to Node backend format.
 * Usage: node scripts/mysql-to-json.js [input.json]
 * Default input: u556329104_localhost.json in project root
 */

const fs = require('fs');
const path = require('path');

const PROJECT_ROOT = path.join(__dirname, '..');
const DEFAULT_INPUT = path.join(PROJECT_ROOT, 'u556329104_localhost.json');
const DATA_DIR = path.join(PROJECT_ROOT, 'data');
const PRODUCTS_FILE = path.join(DATA_DIR, 'products.json');
const CATEGORIES_FILE = path.join(DATA_DIR, 'categories.json');

const inputPath = process.argv[2] || DEFAULT_INPUT;

if (!fs.existsSync(inputPath)) {
    console.error(`Input file not found: ${inputPath}`);
    process.exit(1);
}

const raw = fs.readFileSync(inputPath, 'utf8');
let exportData;
try {
    exportData = JSON.parse(raw);
} catch (e) {
    console.error('Invalid JSON in input file');
    process.exit(1);
}

// phpMyAdmin export is an array of objects: { type, name, data, ... }
function extractTable(tableName) {
    const table = exportData.find(
        (item) => item.type === 'table' && item.name === tableName
    );
    return table ? table.data || [] : [];
}

const categories = extractTable('categories');
const products = extractTable('products');

// Convert products to Node format (snake_case to match PHP API / frontend)
const convertedProducts = products.map((p) => ({
    id: parseInt(p.id, 10),
    name: p.name,
    category: p.category,
    subcategory: p.subcategory || null,
    regular_price: parseFloat(p.regular_price) || 0,
    large_price: parseFloat(p.large_price) || 0,
    type: p.type,
    description: p.description || '',
}));

// Convert categories for Node (if backend adds /api/categories later)
const convertedCategories = categories
    .filter((c) => c.active === '1' || c.active === 1)
    .map((c) => ({
        id: parseInt(c.id, 10),
        name: c.name,
        slug: c.slug,
        description: c.description || '',
        subtitle: c.subtitle || '',
        icon: c.icon || '',
        display_order: parseInt(c.display_order, 10) || 0,
    }))
    .sort((a, b) => a.display_order - b.display_order);

if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}

fs.writeFileSync(PRODUCTS_FILE, JSON.stringify(convertedProducts, null, 2));
console.log(`Wrote ${convertedProducts.length} products to ${PRODUCTS_FILE}`);

fs.writeFileSync(CATEGORIES_FILE, JSON.stringify(convertedCategories, null, 2));
console.log(`Wrote ${convertedCategories.length} categories to ${CATEGORIES_FILE}`);
