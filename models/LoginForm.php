<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Model;

final class LoginForm extends Model
{
    public string $username = '';
    public string $password = '';
    public bool $rememberMe = true;
    private ?User $user = null;
    private bool $userLoaded = false;

    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword(string $attribute, mixed $params): void
    {
        if (!$this->hasErrors() && !$this->getUser()?->validatePassword($this->password)) {
            $this->addError($attribute, 'Неверное имя пользователя или пароль.');
        }
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
    }

    public function getUser(): ?User
    {
        if (!$this->userLoaded) {
            $this->user = User::findByUsername($this->username);
            $this->userLoaded = true;
        }

        return $this->user;
    }

    public function attributeLabels(): array
    {
        return [
            'username' => 'Имя пользователя',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня',
        ];
    }
}
