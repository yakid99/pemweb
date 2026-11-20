<?php

namespace App\Http\Controllers;

use App\Models\Ioc;
use Illuminate\Http\Request;

class IocController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | READ - MENAMPILKAN DATA IOC
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = Ioc::query();

        // Filter berdasarkan tipe IOC
        if ($request->filled('type')) {

            $query->where(
                'type',
                $request->string('type')->toString()
            );
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->string('status')->toString()
            );
        }

        // Filter tanggal awal
        if ($request->filled('date_from')) {

            $query->whereDate(
                'source_date',
                '>=',
                $request->date('date_from')
            );
        }

        // Filter tanggal akhir
        if ($request->filled('date_to')) {

            $query->whereDate(
                'source_date',
                '<=',
                $request->date('date_to')
            );
        }

        // Global search
        if ($request->filled('search')) {

            $term = '%' .
                trim($request->string('search')->toString())
                . '%';

            $query->where(function ($q) use ($term) {

                $q->where('value', 'like', $term)

                    ->orWhere(
                        'source_user',
                        'like',
                        $term
                    )

                    ->orWhere(
                        'category',
                        'like',
                        $term
                    )

                    ->orWhereRaw(
                        'CAST(tags AS CHAR) LIKE ?',
                        [$term]
                    );

            });
        }

        // Pagination 20 data per halaman
        $iocs = $query
            ->orderByDesc('source_date')
            ->paginate(20)
            ->withQueryString();

        return view(
            'iocs.index',
            compact('iocs')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI NILAI IOC MANUAL
    |--------------------------------------------------------------------------
    */

    private function validIndicator(
        string $type,
        string $value
    ): bool {

        return match ($type) {

            'url' => filter_var(
                $value,
                FILTER_VALIDATE_URL
            ) !== false,

            'domain' => preg_match(
                '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                $value
            ) === 1,

            'ip' => filter_var(
                $value,
                FILTER_VALIDATE_IP
            ) !== false,

            'sha256' => preg_match(
                '/\A[a-f0-9]{64}\z/i',
                $value
            ) === 1,

            'md5' => preg_match(
                '/\A[a-f0-9]{32}\z/i',
                $value
            ) === 1,

            default => false,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE - MENAMBAHKAN IOC MANUAL
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $data = $request->validate([

            'type' => 'required|in:url,domain,ip,sha256,md5',

            'value' => 'required|string|max:5000',

            'source_user' => 'nullable|string|max:150',

            'source_tweet' => 'nullable|url|max:2000',

            'tags' => 'nullable|string|max:2000',

            'category' => 'nullable|string|max:100',

            'severity' => 'required|in:info,warning,critical',

            'status' => 'required|in:active,inactive,false_positive',

            'source_date' => 'nullable|date',

        ]);

        // Validasi sesuai tipe indikator
        if (!$this->validIndicator(
            $data['type'],
            $data['value']
        )) {

            return back()
                ->withErrors([
                    'value' => 'Format nilai IOC tidak sesuai dengan tipe yang dipilih.'
                ])
                ->withInput();
        }

        // Memecah tag berdasarkan koma
        $tags = collect(
            explode(',', $data['tags'] ?? '')
        )
        ->map(fn ($tag) => trim($tag))
        ->filter()
        ->values()
        ->all();

        // Membuat identitas unik IOC manual
        $sourceKey = hash(
            'sha256',
            'manual|' .
            $data['type'] .
            '|' .
            mb_strtolower($data['value'])
        );

        // Menyimpan data dan mencegah duplikasi
        $ioc = Ioc::firstOrCreate(

            [
                'source_key' => $sourceKey
            ],

            [

                'type' => $data['type'],

                'value' => $data['value'],

                'source_user' => $data['source_user'] ?? null,

                'source_tweet' => $data['source_tweet'] ?? null,

                'tags' => $tags,

                'category' => $data['category']
                    ?? (isset($tags[0])
                        ? ltrim($tags[0], '#')
                        : 'Manual'),

                'severity' => $data['severity'],

                'status' => $data['status'],

                'source_date' => $data['source_date']
                    ?? now('Asia/Jakarta'),

                'source' => 'manual',

            ]
        );

        return redirect()
            ->route('iocs.index')
            ->with(
                'success',
                $ioc->wasRecentlyCreated
                    ? 'IOC berhasil ditambahkan.'
                    : 'IOC manual dengan nilai tersebut sudah tercatat.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE - MENGUBAH DATA IOC
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Ioc $ioc
    ) {

        $data = $request->validate([

            'tags' => 'nullable|string|max:2000',

            'category' => 'nullable|string|max:100',

            'severity' => 'required|in:info,warning,critical',

            'status' => 'required|in:active,inactive,false_positive',

        ]);

        // Konversi tag menjadi array
        $data['tags'] = collect(
            explode(',', $data['tags'] ?? '')
        )
        ->map(fn ($tag) => trim($tag))
        ->filter()
        ->values()
        ->all();

        // Memperbarui IOC
        $ioc->update($data);

        return redirect()
            ->route('iocs.index')
            ->with(
                'success',
                'Data IOC berhasil diperbarui.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE - MENGHAPUS DATA IOC
    |--------------------------------------------------------------------------
    */

    public function destroy(Ioc $ioc)
    {
        $ioc->delete();

        return redirect()
            ->route('iocs.index')
            ->with(
                'success',
                'Data IOC berhasil dihapus.'
            );
    }
}
