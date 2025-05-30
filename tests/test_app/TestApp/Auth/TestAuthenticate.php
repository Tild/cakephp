<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * TestAuthenticate class
 */
class TestAuthenticate extends BaseAuthenticate
{
    public $callStack = [];

    public $authenticationProvider;

    public $modifiedUser;

    /**
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Auth.afterIdentify' => 'afterIdentify',
            'Auth.logout' => 'logout',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function authenticate(ServerRequest $request, Response $response): array
    {
        return ['id' => 1, 'username' => 'admad'];
    }

    /**
     * @param array $user
     * @return array|void
     */
    public function afterIdentify(EventInterface $event, array $user)
    {
        $this->callStack[] = __FUNCTION__;
        $this->authenticationProvider = $event->getData('1');

        if ($this->modifiedUser) {
            $event->setResult($user + ['extra' => 'foo']);
        }
    }

    /**
     * @param array $user
     */
    public function logout(EventInterface $event, array $user): void
    {
        $this->callStack[] = __FUNCTION__;
    }
}
