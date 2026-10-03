<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use Tests\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredFailsOnEmpty(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('required', strtolower((string) $v->firstError()));
    }

    public function testRequiredPassesWithValue(): void
    {
        $v = Validator::make(['name' => 'Sethembiso'], ['name' => 'required']);
        $this->assertFalse($v->fails());
    }

    public function testEmailRejectsInvalid(): void
    {
        $v = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertTrue($v->fails());
    }

    public function testEmailAcceptsValid(): void
    {
        $v = Validator::make(['email' => 'user@example.com'], ['email' => 'email']);
        $this->assertFalse($v->fails());
    }

    public function testMinLength(): void
    {
        $v = Validator::make(['password' => 'short'], ['password' => 'min:8']);
        $this->assertTrue($v->fails());

        $v = Validator::make(['password' => 'longenough'], ['password' => 'min:8']);
        $this->assertFalse($v->fails());
    }

    public function testMaxLength(): void
    {
        $v = Validator::make(['bio' => str_repeat('x', 200)], ['bio' => 'max:100']);
        $this->assertTrue($v->fails());
    }

    public function testAlphaDash(): void
    {
        $v = Validator::make(['username' => 'user name!'], ['username' => 'alpha_dash']);
        $this->assertTrue($v->fails());

        $v = Validator::make(['username' => 'user_123-x'], ['username' => 'alpha_dash']);
        $this->assertFalse($v->fails());
    }

    public function testNumeric(): void
    {
        $v = Validator::make(['price' => 'abc'], ['price' => 'numeric']);
        $this->assertTrue($v->fails());

        $v = Validator::make(['price' => '12.50'], ['price' => 'numeric']);
        $this->assertFalse($v->fails());
    }

    public function testConfirmed(): void
    {
        $v = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'different'],
            ['password' => 'confirmed']
        );
        $this->assertTrue($v->fails());

        $v = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'secret123'],
            ['password' => 'confirmed']
        );
        $this->assertFalse($v->fails());
    }

    public function testIn(): void
    {
        $v = Validator::make(['role' => 'hacker'], ['role' => 'in:customer,staff,admin']);
        $this->assertTrue($v->fails());

        $v = Validator::make(['role' => 'admin'], ['role' => 'in:customer,staff,admin']);
        $this->assertFalse($v->fails());
    }

    public function testNullablePassesWhenEmpty(): void
    {
        $v = Validator::make(['phone' => ''], ['phone' => 'nullable|min:5']);
        $this->assertFalse($v->fails(), 'nullable should skip further rules when empty');
    }

    public function testChainStopsAtFirstFailure(): void
    {
        $v = Validator::make(['email' => ''], ['email' => 'required|email|min:5']);
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('required', strtolower((string) $v->firstError()));
    }

    public function testErrorsIsAssociative(): void
    {
        $v = Validator::make(
            ['name' => '', 'email' => 'bad'],
            ['name' => 'required', 'email' => 'email']
        );
        $errors = $v->errors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testFirstStringIsAliasForFirstError(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertSame($v->firstError(), $v->firstString());
    }
}