<?php

namespace App\Helpers;

class SessionHelper
{
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return session()->has('user_id');
    }

    /**
     * Get current logged-in user ID
     */
    public static function getUserId(): ?int
    {
        return session()->get('user_id');
    }

    /**
     * Get current logged-in user username
     */
    public static function getUsername(): ?string
    {
        return session()->get('user_name');
    }

    /**
     * Get current logged-in user role
     */
    public static function getRole(): ?string
    {
        return session()->get('role');
    }

    /**
     * Get current logged-in user email
     */
    public static function getEmail(): ?string
    {
        return session()->get('email');
    }

    /**
     * Check if user has a specific role
     */
    public static function hasRole(string $role): bool
    {
        return session()->get('role') === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        return in_array(session()->get('role'), $roles);
    }

    /**
     * Get all user session data
     */
    public static function getUserData(): array
    {
        return [
            'user_id' => session()->get('user_id'),
            'username' => session()->get('user_name'),
            'role' => session()->get('role'),
            'email' => session()->get('email'),
            'logged_in_at' => session()->get('logged_in_at'),
        ];
    }

    /**
     * Clear session (logout)
     */
    public static function clearSession(): void
    {
        session()->flush();
    }
}
