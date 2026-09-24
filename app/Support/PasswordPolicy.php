<?php

namespace App\Support;

/**
 * One password policy, in one place.
 *
 * It had drifted into four: registration allowed 100 characters, every screen
 * that changes a password allowed 32, and login demanded at least 6. So a
 * customer who registered with a 40-character password from a password
 * manager could sign in with it but could never change it, and was told only
 * that it "may not be greater than 32 characters".
 *
 * The ceiling is 72 because bcrypt, which hashes these, silently ignores
 * anything past 72 bytes. Allowing 100 meant the last 28 characters of a long
 * password did nothing at all while appearing to be part of it.
 */
class PasswordPolicy
{
    public const MINIMUM = 8;

    /** bcrypt truncates beyond this, so accepting more is a false promise. */
    public const MAXIMUM = 72;

    /**
     * For setting a password: registering, changing one, or an administrator
     * creating an account.
     *
     * @param  bool  $optional  true where a blank field means "leave it alone"
     */
    public static function rules(bool $confirmed = true, bool $optional = false): array
    {
        return array_values(array_filter([
            $optional ? 'nullable' : 'required',
            'string',
            'min:' . self::MINIMUM,
            'max:' . self::MAXIMUM,
            $confirmed ? 'confirmed' : null,
        ]));
    }

    /**
     * For checking a password that already exists.
     *
     * No minimum: a length rule on sign-in can only ever reject somebody who
     * would have failed anyway, and it answers differently for "too short"
     * than for "wrong", which is a distinction no one signing in should be
     * given. The ceiling stays generous so that an older, longer password is
     * still accepted rather than rejected before it is even checked.
     */
    public static function loginRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    public static function messages(string $field = 'password'): array
    {
        return [
            $field . '.required' => 'Password is required.',
            $field . '.min' => 'Password must be at least ' . self::MINIMUM . ' characters.',
            $field . '.max' => 'Password must be ' . self::MAXIMUM . ' characters or fewer.',
            $field . '.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
