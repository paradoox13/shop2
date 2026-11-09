<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Http\Controller;
use App\Http\Request;
use App\Models\Modem;
use App\Models\User;
use App\Services\ImageService;
use App\Support\Audit;
use App\Support\Auth;
use RuntimeException;

final class ModemController extends Controller
{
    public function index(Request $request): string
    {
        $page = max(1, $request->int('page', 1));

        return $this->view('reseller/modems/index', [
            'title' => 'مودم‌های من',
            'modems' => Modem::search($request->all(), $page, (int) config('pagination.admin', 20), (int) Auth::id(), false),
            'filters' => $request->all(),
        ], 'reseller');
    }

    public function create(): string
    {
        return $this->view('reseller/modems/form', [
            'title' => 'ثبت مودم',
            'modem' => null,
            'action' => url('/reseller/modems/store'),
        ], 'reseller');
    }

    public function store(Request $request): string
    {
        $data = $this->validated($request);
        $imagePath = null;
        $file = $request->file('image');

        if ($file !== null) {
            try {
                $imagePath = (new ImageService())->store($file, 'modems');
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                $this->back('/reseller/modems/create');
            }
        }

        try {
            $id = Modem::create([
                'brand' => $data['brand'],
                'model' => $data['model'],
                'price' => (int) $data['price'],
                'stock' => (int) $data['stock'],
                'description' => $data['description'] ?? null,
                'image_path' => $imagePath,
                'reseller_id' => (int) Auth::id(),
                'status' => (string) $data['status'],
            ]);
            Audit::log('reseller.modem_created', 'modems', (string) $id);
            flash('success', 'مودم ثبت شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/reseller/modems/create');
        }

        $this->redirect('/reseller/modems');
        return '';
    }

    public function edit(int $id): string
    {
        $modem = $this->owned($id);

        return $this->view('reseller/modems/form', [
            'title' => 'ویرایش مودم',
            'modem' => $modem,
            'action' => url('/reseller/modems/' . $id . '/update'),
        ], 'reseller');
    }

    public function update(Request $request, int $id): string
    {
        $modem = $this->owned($id);
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
                $this->back('/reseller/modems/' . $id . '/edit');
            }
        }

        try {
            Modem::update($id, [
                'brand' => $data['brand'],
                'model' => $data['model'],
                'price' => (int) $data['price'],
                'stock' => (int) $data['stock'],
                'description' => $data['description'] ?? null,
                'image_path' => $imagePath,
                'reseller_id' => (int) Auth::id(),
                'status' => (string) $data['status'],
            ]);
            User::flushResellerCache();
            Audit::log('reseller.modem_updated', 'modems', (string) $id);
            flash('success', 'مودم به‌روزرسانی شد.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->back('/reseller/modems/' . $id . '/edit');
        }

        $this->redirect('/reseller/modems');
        return '';
    }

    public function delete(Request $request, int $id): string
    {
        $modem = $this->owned($id);

        if ($modem['image_path'] !== null) {
            (new ImageService())->delete((string) $modem['image_path']);
        }

        Modem::delete($id);
        Audit::log('reseller.modem_deleted', 'modems', (string) $id);
        flash('success', 'مودم حذف شد.');
        $this->redirect('/reseller/modems');

        return '';
    }

    /** @return array<string, mixed> */
    private function owned(int $id): array
    {
        $modem = Modem::find($id);

        if ($modem === null) {
            throw new NotFoundException('مودم یافت نشد.');
        }

        if ((int) $modem['reseller_id'] !== (int) Auth::id()) {
            throw new ForbiddenException('این مودم متعلق به شما نیست.');
        }

        return $modem;
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
            'status' => 'required|in:active,inactive',
        ], [
            'brand' => 'برند',
            'model' => 'مدل',
            'price' => 'قیمت',
            'stock' => 'موجودی',
        ]);
    }
}
