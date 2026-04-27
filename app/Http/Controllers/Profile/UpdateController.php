<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Jobs\RecordAuditLog;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;

class UpdateController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    public function __invoke(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar_file')) {
            $result = $this->cloudinary->uploadProductImage($request->file('avatar_file'), 'avatars');
            $data['avatar'] = $result['url'];
        }

        // Removemos avatar_file del array para no intentar guardarlo directamente en la BD
        unset($data['avatar_file']);

        $user->update($data);

        // Broadcasting para actualización en tiempo real
        event(new \App\Events\ProfileUpdated($user));

        RecordAuditLog::dispatch('profile_updated', $user->id);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
