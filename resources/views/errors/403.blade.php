<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Permission Denied — NanoDrive</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white border border-zinc-200 rounded-lg p-7 space-y-4">
        <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
            <span class="font-mono text-xs font-semibold text-red-600">HTTP 403 · EACCES</span>
            <span class="font-mono text-xs text-zinc-400">NanoDrive Policy</span>
        </div>

        <div class="space-y-1.5">
            <h1 class="text-lg font-semibold text-zinc-900">Permission denied</h1>
            <p class="text-sm text-zinc-600 leading-relaxed">
                Evaluasi hak akses (<span class="font-mono text-xs text-zinc-800">Owner → Group → Others</span>) menolak permintaan ini karena akun Anda tidak memiliki bit izin (<code class="font-mono text-xs bg-zinc-100 px-1 py-0.5 rounded">r</code>, <code class="font-mono text-xs bg-zinc-100 px-1 py-0.5 rounded">w</code>, atau <code class="font-mono text-xs bg-zinc-100 px-1 py-0.5 rounded">x</code>) yang diperlukan.
            </p>
        </div>

        <div class="pt-2 flex items-center gap-2.5">
            <a href="{{ url()->previous() }}" class="px-3.5 py-1.5 bg-white border border-zinc-300 hover:bg-zinc-50 text-zinc-700 rounded-md text-xs font-medium transition">
                Go back
            </a>
            <a href="{{ route('drive.index') }}" class="px-3.5 py-1.5 bg-zinc-900 hover:bg-zinc-800 text-white rounded-md text-xs font-medium transition">
                Return to root
            </a>
        </div>
    </div>
</body>
</html>