<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class MateriController extends Controller
{
    public function index()
    {
        $data = Materi::with(['user:id,name,email,avatar'])->get();

        return $this->responseSuccess('Data', $data);
    }

    public function show($id)
    {
        $data = Materi::where('id', $id)->with(['user:id,name,email,avatar'])->first();
        if (!$data) return $this->responseFailed('Data tidak ditemukan', '', 404);

        return $this->responseSuccess('Detail data', $data, 200);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'subject' => 'required|string',
            'competence' => 'required|string',
            'class' => 'required|string',
            'semester' => 'required|string',
            'meet' => 'required|string',
            'description' => 'required|string',
            'image_banner' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('image_banner')) {
            $input['image_banner'] = rand().'.'.request()->image_banner->getClientOriginalExtension();
            
            request()->image_banner->move(public_path('assets/images/materi/'), $input['image_banner']);
        }

        $data = Materi::create([
            'user_id' => auth()->user()->id,
            'subject' => $input['subject'],
            'competence' => $input['competence'],
            'class' => $input['class'],
            'semester' => $input['semester'],
            'meet' => $input['meet'],
            'description' => $input['description'],
            'image_banner' => $input['image_banner'] ?? null
        ]);

        return $this->responseSuccess('Data berhasil dibuat', $data, 201);
    }

    public function update($id, Request $request)
    {
        $materi = Materi::where('id', $id)->first();
        if (!$materi) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $input = $request->all();
        $validator = Validator::make($input, [
            'subject' => 'required|string',
            'competence' => 'required|string',
            'class' => 'required|string',
            'semester' => 'required|string',
            'meet' => 'required|string',
            'description' => 'required|string',
            'image_banner' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        $oldImage = $materi->image_banner;
        if ($request->hasFile('image_banner')) {
            File::delete('assets/images/materi/' . $oldImage);
            $input['image_banner'] = rand().'.'.request()->image_banner->getClientOriginalExtension();

            request()->image_banner->move(public_path('assets/images/materi/'), $input['image_banner']);
        } else {
            $input['image_banner'] = $oldImage;
        }

        $materi->update($input);

        $data = Materi::find($id);

        return $this->responseSuccess('Data berhasil diubah', $data, 200);
    }

    public function destroy($id)
    {
        $materi = Materi::where('id', $id)->first();
        if (!$materi) return $this->responseFailed('Data tidak ditemukan', '', 404);

        if ($materi->image_banner) {
            File::delete('assets/images/materi/' . $materi->image_banner);
        }

        $materi->delete();

        return $this->responseSuccess('Data berhasil dihapus');
    }

    public function deleteMateriImage($id)
    {
        $materi = Materi::find($id);
        if (!$materi) return $this->responseFailed('Data tidak ditemukan', '', 404);
        if (!$materi->image_banner) return $this->responseFailed('Gambar tidak ada', '', 400);

        File::delete('assets/images/materi/' . $materi->image_banner);
        $materi->update(['image_banner' => null]);

        return $this->responseSuccess('Gambar berhasil dihapus');
    }

    public function uploadImage(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'files' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('file')) {
            $input['file'] = rand().'.'.request()->file->getClientOriginalExtension();
            
            request()->file->move(public_path('assets/images/ck/'), $input['file']);
        }

        $data = Image::create(['file' => $input['file'] ?? null]);

        return $this->responseSuccess('Data berhasil dibuat', $data, 201);
    }
}
