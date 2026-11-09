<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Models\Modem;
use App\Models\User;
use App\Services\ImageService;
use App\Support\Audit;
use RuntimeException;
use Throwable;

final class ModemController extends Controller
{
    public function index(Request $request): string
    {
        $this->requirePermission('modems.manage');
        $page = max(1, $request->int('page', 1));
        $filters = $request->all();

        return $this->view('admin/modems/index', [
            'title' => 'مدیریت مودم‌ها',
            'modems' => Modem::search($filters, $page, (int) config('pagination.admin', 20), null, false),
            'filters' => $filters,
            'brands' => Modem::brands(),
        ], 'admin');
    }

    public function create(): string
    {
        $this->requirePermission('modems.manage');

        return $this->view('admin/modems/form', [
            'title' => 'افزودن مودم',
            'modem' => null,
            'resellers' => User::resellersForSelect(),
            'action' => url('/admin/modems/store'),
        ], 'admin');
    }

    public function store(Request $request): string
    {
        $this->requirePermission('modems.manage');
        $data = $this->validated($request);
        $imagePath = null;
        $file = $request->file('image');

        if ($file !== null) {
            try {
                $imagePath = (new ImageService())->store($file, 'modems');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                $this->back('/admin/modems/create');
            }
        }

        try {
            $id = Modem::create([
                'brand' => $data['brand'],
                'model' => $data['model'],
                'price' => (int) $data['price'],
                'stock' => (int) $data['stock'],
                'description' => $data['description'] ?? null,
                'reseller_id' => (int) ($data['reseller_id'] ?? 0),
                'status' => (string) $data['status'],
                'image_path' => $imagePath,
            ]);
            Audit::log('modem.created', 'modems', (string) $id);
            flash('success', 'مودم ثبت شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/admin/modems/create');
        }

        $this->redirect('/admin/modems');
        return '';
    }

    public function edit(int $id): string
    {
        $this->requirePermission('modems.manage');
        $modem = Modem::find($id);

        if ($modem === null) {
            throw new NotFoundException('مودم یافت نشد.');
        }

        return $this->view('admin/modems/form', [
            'title' => 'ویرایش مودم',
            'modem' => $modem,
            'resellers' => User::resellersForSelect(),
            'action' => url('/admin/modems/' . $id . '/update'),
        ], 'admin');
    }

    public function update(Request $request, int $id): string
    {
        $this->requirePermission('modems.manage');
        $modem = Modem::find($id);

        if ($modem === null) {
            throw new NotFoundException('مودم یافت نشد.');
        }

        $data = $this->validated($request);
        $imagePath = $modem['image_path'];
        $file = $request->file('image');

        if ($file !== null) {
            try {
                $newImage = (new ImageService())->store($file, 'modems');

                if ($imagePath !== null) {
                    (new ImageService())->delete((string) $imagePath);
                }

                $imagePath = $newImage;
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                $this->back('/admin/modems/' . $id . '/edit');
            }
        }

        try {
            Modem::update($id, [
                'brand' => $data['brand'],
                'model' => $data['model'],
                'price' => (int) $data['price'],
                'stock' => (int) $data['stock'],
                'description' => $data['description'] ?? null,
                'reseller_id' => (int) ($data['reseller_id'] ?? 0),
                'status' => (string) $data['status'],
                'image_path' => $imagePath,
            ]);
            Audit::log('modem.updated', 'modems', (string) $id);
            flash('success', 'مودم به‌روزرسانی شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/admin/modems/' . $id . '/edit');
        }

        $this->redirect('/admin/modems');
        return '';
    }

    public function delete(Request $request, int $id): string
    {
        $this->requirePermission('modems.manage');
        $modem = Modem::find($id);

        if ($modem === null) {
            flash('error', 'مودم یافت نشد.');
            $this->redirect('/admin/modems');
        }

        try {
            if ($modem['image_path'] !== null) {
                (new ImageService())->delete((string) $modem['image_path']);
            }

            Modem::delete($id);
            Audit::log('modem.deleted', 'modems', (string) $id);
            flash('success', 'مودم حذف شد.');
        } catch (Throwable) {
            flash('error', 'حذف مودم ممکن نشد.');
        }

        $this->redirect('/admin/modems');
        return '';
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $this->validate($request, [
            'brand' => 'required|min:2|max:60',
            'model' => 'required|min:1|max:80',
            'price' => 'required|integer|min:1000',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|max:5000',
            'reseller_id' => 'nullable|integer|exists:users,id',
            'status' => 'required|in:active,inactive',
        ], [
            'brand' => 'برند',
            'model' => 'مدل',
            'price' => 'قیمت',
            'stock' => 'موجودی',
            'reseller_id' => 'نماینده',
        ]);
    }
}
