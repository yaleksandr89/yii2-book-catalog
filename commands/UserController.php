<?php

declare(strict_types=1);

namespace app\commands;

use app\models\User;
use Yii;
use yii\base\Exception as YiiBaseException;
use yii\console\Controller;
use yii\console\ExitCode;

final class UserController extends Controller
{
    /**
     * @throws YiiBaseException
     */
    public function actionCreate(string $username = '', string $password = ''): int
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            $this->stderr("Имя пользователя и пароль обязательны.\n");

            return ExitCode::DATAERR;
        }

        if (User::findByUsername($username) !== null) {
            $this->stderr("Пользователь с таким именем уже существует.\n");

            return ExitCode::DATAERR;
        }

        $user = new User([
            'username' => $username,
            'password_hash' => Yii::$app->security->generatePasswordHash($password),
            'auth_key' => Yii::$app->security->generateRandomString(),
        ]);

        if (!$user->save()) {
            $this->stderr("Не удалось создать пользователя.\n");

            return ExitCode::DATAERR;
        }

        $this->stdout("Пользователь создан.\n");

        return ExitCode::OK;
    }
}
