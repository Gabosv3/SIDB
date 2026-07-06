<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AppUpdateController extends Controller
{
    private const APK_NOMBRE = 'sidb.apk';

    public function index(): View
    {
        return view('admin.update', [
            'actual' => $this->versionActual(),
        ]);
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'apk' => [
                'required',
                'file',
                'max:256000', // 250 MB en KB (los builds de release reales rondan ~135-150 MB)
                function ($attribute, $value, $fail) {
                    if (strtolower($value->getClientOriginalExtension()) !== 'apk') {
                        $fail('El archivo debe tener extensión .apk.');
                    }
                },
            ],
            'version' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], [
            'version.regex' => 'La versión debe tener el formato x.x.x (ej: 1.0.1).',
        ]);

        $directorio = public_path('update');
        if (! File::isDirectory($directorio)) {
            File::makeDirectory($directorio, 0755, true);
        }

        $request->file('apk')->move($directorio, self::APK_NOMBRE);

        $version = [
            'version' => $data['version'],
            'url' => url('/update/'.self::APK_NOMBRE),
            'notas' => $data['notas'] ?? '',
        ];

        File::put(
            $directorio.'/version.json',
            json_encode($version, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return redirect()->route('admin.update.index')->with('success', 'APK publicado correctamente.');
    }

    private function versionActual(): ?array
    {
        $archivo = public_path('update/version.json');

        if (! File::exists($archivo)) {
            return null;
        }

        $contenido = json_decode(File::get($archivo), true);

        return is_array($contenido) ? $contenido : null;
    }
}
