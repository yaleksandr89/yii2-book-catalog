<?php

declare(strict_types=1);

namespace app\widgets;

use Yii;
use yii\bootstrap5\Alert as BootstrapAlert;
use yii\web\Application;

/**
 * RU: Виджет показывает флеш-сообщения сессии. Через setFlash можно передать
 * строку или массив сообщений. Внутри каждого типа сохраняется порядок сообщений.
 * EN: The widget displays session flash messages. Pass a string or an array
 * of messages to setFlash. Messages retain their order within each type.
 *
 * ```php
 * Yii::$app->session->setFlash('error', 'This is the message');
 * Yii::$app->session->setFlash('success', 'This is the message');
 * Yii::$app->session->setFlash('info', 'This is the message');
 * ```
 *
 * ```php
 * Yii::$app->session->setFlash('error', ['Error 1', 'Error 2']);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Alert extends \yii\bootstrap5\Widget
{
    /**
     * RU: Сопоставляет типы флеш-сообщений с CSS-классами уведомлений Bootstrap.
     * EN: Maps flash message types to Bootstrap alert CSS classes.
     *
     * @var array<string, string>
     */
    public array $alertTypes = [
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'success' => 'alert-success',
        'info'    => 'alert-info',
        'warning' => 'alert-warning',
    ];
    /**
     * RU: Параметры кнопки закрытия, передаваемые в [[BootstrapAlert::closeButton]].
     * EN: Options for the close button passed to [[BootstrapAlert::closeButton]].
     *
     * @var array<string, mixed>
     */
    public array $closeButton = [];
    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $app = Yii::$app;
        assert($app instanceof Application);
        $session = $app->session;

        if (!$session->getIsActive() && !$session->getHasSessionId()) {
            return;
        }

        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach (array_keys($this->alertTypes) as $type) {
            $flash = $session->getFlash($type);

            foreach ((array) $flash as $i => $message) {
                echo BootstrapAlert::widget(
                    [
                        'body' => $message,
                        'closeButton' => $this->closeButton,
                        'options' => [
                            ...$this->options,
                            'id' => $this->getId() . '-' . $type . '-' . $i,
                            'class' => $this->alertTypes[$type] . $appendClass,
                        ],
                    ],
                );
            }

            $session->removeFlash($type);
        }
    }
}
