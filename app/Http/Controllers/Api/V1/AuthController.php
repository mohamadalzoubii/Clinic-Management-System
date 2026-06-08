<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\VerifyOtpAction;
use App\DTOs\Auth\LoginUserData;
use App\DTOs\Auth\RegisterUserData;
use App\DTOs\Auth\VerifyOtpData;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\verifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponses;

    public function register(RegisterRequest $request, RegisterAction $action)
    {

        $validated = $request->validated();

        $dto = new RegisterUserData(
            first_name: $validated['first_name'],
            last_name: $validated['last_name'],
            email: $validated['email'],
            password: $validated['password'],
        );

        $user = $action->execute($dto);

        return $this->ok(
            'Your account has been created please check your email to enter the code',
            [
                'user' => new UserResource($user),
            ]
        );
    }

    public function verify(verifyOtpRequest $request, VerifyOtpAction $action)
    {
        $validated = $request->validated();

        $dto = new VerifyOtpData(
            email: $validated['email'],
            code: $validated['code']
        );

        $result = $action->execute($dto);

        return $this->ok(
            'Welcome, your account is active and you are authenticated',
            [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ]
        );
    }

    public function Login(LoginRequest $request, LoginAction $action)
    {
        $validated = $request->validated();

        $dto = new LoginUserData(
            email: $validated['email'],
            password: $validated['password']
        );

        $result = $action->execute($dto, RoleEnum::PATIENT->value);

        return $this->ok('Authnticated', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function LoginAsPatientForSecretary(LoginRequest $request, LoginAction $action)
    {
        $validated = $request->validated();

        $dto = new LoginUserData(
            email: $validated['email'],
            password: $validated['password']
        );

        $result = $action->execute($dto, RoleEnum::PATIENT->value, false);

        return $this->ok('Authnticated', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function DoctorLogin(LoginRequest $request, LoginAction $action)
    {
        $validated = $request->validated();

        $dto = new LoginUserData(
            email: $validated['email'],
            password: $validated['password']
        );

        $result = $action->execute($dto, RoleEnum::DOCTOR->value);

        return $this->ok('Authnticated', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function AdminLogin(LoginRequest $request, LoginAction $action)
    {
        $validated = $request->validated();

        $dto = new LoginUserData(
            email: $validated['email'],
            password: $validated['password']
        );

        $result = $action->execute($dto, RoleEnum::ADMIN->value);

        return $this->ok('Authnticated', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function SecretaryLogin(LoginRequest $request, LoginAction $action)
    {
        $validated = $request->validated();

        $dto = new LoginUserData(
            email: $validated['email'],
            password: $validated['password']
        );

        $result = $action->execute($dto, RoleEnum::SECRETARY->value);

        return $this->ok('Authnticated', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function Logout(Request $request, LogoutAction $action)
    {
        $user = $request->user();

        $action->execute($user);

        return $this->ok('you logout successfully');
    }
}
