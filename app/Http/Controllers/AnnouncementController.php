<?php

namespace App\Http\Controllers;

use App\Http\Requests\Announcement\AnnouncementIndexRequest;
use App\Http\Requests\Announcement\AnnouncementStoreRequest;
use App\Models\Announcement;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    private const LIST_FIELDS = ['id', 'title', 'content', 'image', 'created_at'];

    public function employeeIndex()
    {
        $announcements = Announcement::select(self::LIST_FIELDS)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('employees/Announcements', [
            'announcements' => $announcements,
        ]);
    }

    public function index(AnnouncementIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $filters = [
            'filter_search' => $request->validated('filter_search'),
        ];

        $announcements = Announcement::select(self::LIST_FIELDS)
            ->filter($filters, ['title'])
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('announcements/List', [
            'data' => $announcements,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('announcements/Form');
    }

    public function edit(Announcement $announcement)
    {
        return Inertia::render('announcements/Form', [
            'announcement' => $announcement,
        ]);
    }

    public function store(AnnouncementStoreRequest $request)
    {
        $announcement = Announcement::create($request->validated());

        return to_route('announcements.index')->with('success', "Anuncio {$announcement->title} creado con éxito.");
    }

    public function update(AnnouncementStoreRequest $request, Announcement $announcement)
    {
        $data = $request->validated();

        if (array_key_exists('image', $data) && $announcement->getRawOriginal('image')) {
            Storage::disk('public')->delete($announcement->getRawOriginal('image'));
        }

        $announcement->update($data);

        return to_route('announcements.index')->with('success', "Anuncio {$announcement->title} actualizado con éxito.");
    }

    public function destroy(Announcement $announcement)
    {
        $title = $announcement->title;

        if ($announcement->getRawOriginal('image')) {
            Storage::disk('public')->delete($announcement->getRawOriginal('image'));
        }

        $announcement->delete();

        return to_route('announcements.index')->with('success', "Anuncio {$title} eliminado con éxito.");
    }
}