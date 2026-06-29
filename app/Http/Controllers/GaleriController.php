<?php

namespace App\Http\Controllers;

use App\Models\Galeri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GaleriController extends Controller
{
    // GET /galeri — publik, dipanggil dari landing page & admin
    public function index()
    {
        return response()->json(Galeri::latest()->get());
    }

    // POST /galeri — admin upload foto
    public function store(Request $request)
    {
        $request->validate([
            'judul'      => 'required|string|max:255',
            'keterangan' => 'nullable|string|max:500',
            'kategori'   => 'nullable|string|max:100',
            'foto'       => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = $request->file('foto')->store('galeri', 'public');
        $url  = Storage::url($path); // menghasilkan /storage/galeri/namafile.jpg

        $galeri = Galeri::create([
            'judul'      => $request->judul,
            'keterangan' => $request->keterangan ?? '',
            'kategori'   => $request->kategori ?? '',
            'url_foto'   => $url,
        ]);

        return response()->json($galeri, 201);
    }

    // DELETE /galeri/{id} — admin hapus foto
    public function destroy($id)
    {
        $galeri = Galeri::findOrFail($id);

        // Hapus file dari storage juga
        $relativePath = str_replace('/storage/', 'public/', $galeri->url_foto);
        if (Storage::exists($relativePath)) {
            Storage::delete($relativePath);
        }

        $galeri->delete();

        return response()->json(['message' => 'Foto dihapus.']);
    }
}