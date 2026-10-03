<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\AuditLogger;

final class ProductController
{
    private const UPLOAD_DIR = 'assets/uploads/product_images';
    private const MAX_MB     = 4;
    private const MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public function index(Request $request): Response
    {
        $q       = $request->str('q');
        $catId   = $request->int('category', 0);
        $status  = $request->str('status'); // active|inactive|''
        $page    = max(1, $request->int('page', 1));
        $perPage = 15;
        $offset  = ($page - 1) * $perPage;

        $where  = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.name LIKE :q1 OR p.sku LIKE :q2)';
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
        }
        if ($catId > 0) {
            $where[] = 'p.category_id = :cid';
            $params['cid'] = $catId;
        }
        if ($status === 'active')   $where[] = 'p.is_active = 1';
        if ($status === 'inactive') $where[] = 'p.is_active = 0';

        $whereSql = implode(' AND ', $where);
        $total = (int) db()->scalar("SELECT COUNT(*) FROM products p WHERE {$whereSql}", $params);

        $rows = db()->select(
            "SELECT p.*, c.category_name
             FROM products p
             LEFT JOIN categories c ON c.category_id = p.category_id
             WHERE {$whereSql}
             ORDER BY p.product_id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $categories = db()->select('SELECT category_id, category_name FROM categories ORDER BY category_name');

        return Response::html(view('admin.products.index', [
            'title'      => 'Products | Admin',
            'active'     => 'products',
            'rows'       => $rows,
            'categories' => $categories,
            'q'          => $q,
            'catId'      => $catId,
            'status'     => $status,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
        ]));
    }

    public function create(Request $request): Response
    {
        $categories = db()->select('SELECT category_id, category_name FROM categories ORDER BY category_name');

        return Response::html(view('admin.products.form', [
            'title'      => 'New Product | Admin',
            'active'     => 'products',
            'product'    => null,
            'categories' => $categories,
        ]));
    }

    public function store(Request $request): Response
    {
        $data = $this->collectInput($request);

        $validator = Validator::make($data, [
            'name'       => 'required|min:2|max:180',
            'price'      => 'required|numeric',
            'stock'      => 'required|numeric',
            'category_id'=> 'required|numeric',
            'sku'        => 'nullable|max:64|unique:products,sku',
        ]);

        if ($validator->fails()) {
            remember_old($data);
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('admin/products/create'));
        }

        $data['sku']       = $data['sku'] ?: $this->generateSku($data['name']);
        $data['is_active'] = 1;
        $data['image_path'] = $this->handleUpload($request) ?? null;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = db()->insert('products', $data);

        AuditLogger::log('product.create', 'product', (string) $id, null, $data);
        flash('success', 'Product created successfully.');
        return Response::redirect(url('admin/products'));
    }

    public function edit(Request $request, string $id): Response
    {
        $product = db()->selectOne('SELECT * FROM products WHERE product_id = :id', ['id' => (int) $id]);
        if (!$product) {
            return Response::notFound('Product not found.');
        }

        $categories = db()->select('SELECT category_id, category_name FROM categories ORDER BY category_name');

        return Response::html(view('admin.products.form', [
            'title'      => 'Edit Product | Admin',
            'active'     => 'products',
            'product'    => $product,
            'categories' => $categories,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $id      = (int) $id;
        $before  = db()->selectOne('SELECT * FROM products WHERE product_id = :id', ['id' => $id]);
        if (!$before) {
            return Response::notFound('Product not found.');
        }

        $data = $this->collectInput($request);

        $validator = Validator::make($data, [
            'name'       => 'required|min:2|max:180',
            'price'      => 'required|numeric',
            'stock'      => 'required|numeric',
            'category_id'=> 'required|numeric',
        ]);

        if ($validator->fails()) {
            remember_old($data);
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('admin/products/' . $id . '/edit'));
        }

        $data['sku']        = $data['sku'] ?: ($before['sku'] ?? $this->generateSku($data['name']));
        $data['is_active']  = $before['is_active'];
        $data['updated_at'] = date('Y-m-d H:i:s');

        $newImage = $this->handleUpload($request);
        if ($newImage !== null) {
            $data['image_path'] = $newImage;
            $this->deleteFile($before['image_path'] ?? null);
        }

        db()->update('products', $data, 'product_id = :id', ['id' => $id]);

        AuditLogger::log('product.update', 'product', (string) $id, $before, $data);
        flash('success', 'Product updated.');
        return Response::redirect(url('admin/products'));
    }

    public function destroy(Request $request, string $id): Response
    {
        $id     = (int) $id;
        $before = db()->selectOne('SELECT * FROM products WHERE product_id = :id', ['id' => $id]);
        if (!$before) {
            return Response::notFound('Product not found.');
        }

        db()->update(
            'products',
            ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')],
            'product_id = :id',
            ['id' => $id]
        );

        AuditLogger::log('product.delete', 'product', (string) $id, $before, null);
        flash('success', 'Product archived (soft-deleted).');
        return Response::redirect(url('admin/products'));
    }

    public function restore(Request $request, string $id): Response
    {
        $id = (int) $id;
        db()->update(
            'products',
            ['is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')],
            'product_id = :id',
            ['id' => $id]
        );
        AuditLogger::log('product.restore', 'product', (string) $id);
        flash('success', 'Product restored.');
        return Response::redirect(url('admin/products'));
    }

    /* ---------- Helpers ---------- */

    private function collectInput(Request $request): array
    {
        return [
            'name'        => $request->str('name'),
            'sku'         => $request->str('sku'),
            'description' => $request->str('description'),
            'price'       => (float) $request->input('price', 0),
            'stock'       => (int) $request->input('stock', 0),
            'category_id' => (int) $request->input('category_id', 0),
            'low_stock_threshold' => (int) $request->input('low_stock_threshold', 5),
        ];
    }

    private function generateSku(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        $base = substr($base, 0, 8) ?: 'PROD';
        return $base . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function handleUpload(Request $request): ?string
    {
        $file = $request->file('image');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        if ($file['size'] / 1024 / 1024 > self::MAX_MB) {
            flash('warning', 'Image too large — max ' . self::MAX_MB . 'MB.');
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file((string) $file['tmp_name']);
        if (!isset(self::MIMES[$mime])) {
            flash('warning', 'Unsupported image format.');
            return null;
        }
        if (@getimagesize((string) $file['tmp_name']) === false) return null;

        $ext   = self::MIMES[$mime];
        $dir   = base_path('public/' . self::UPLOAD_DIR);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $filename = 'p' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest     = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) return null;

        return self::UPLOAD_DIR . '/' . $filename;
    }

    private function deleteFile(?string $rel): void
    {
        if (!$rel) return;
        $clean = ltrim(str_replace('\\', '/', $rel), '/');
        if (str_starts_with($clean, 'http')) return;
        $full = base_path('public/' . $clean);
        if (is_file($full)) @unlink($full);
    }
}