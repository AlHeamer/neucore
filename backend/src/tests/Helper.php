<?php

namespace Tests;

use Brave\Core\Application;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Brave\Core\Entity\Role;
use Brave\Core\Entity\User;
use Brave\Core\Entity\App;

class Helper
{

    /**
     * @var EntityManager
     */
    private static $em;

    private $entities = [
        'Brave\Core\Entity\App',
        'Brave\Core\Entity\Group',
        'Brave\Core\Entity\Role',
        'Brave\Core\Entity\User',
    ];

    public function resetSessionData()
    {
        unset($_SESSION);

        $rp = new \ReflectionProperty('Brave\Slim\Session\SessionData', 'sess');
        $rp->setAccessible(true);
        $rp->setValue(null);

        $rp = new \ReflectionProperty('Brave\Slim\Session\SessionData', 'readOnly');
        $rp->setAccessible(true);
        $rp->setValue(true);
    }

    public function getEm()
    {
        if (self::$em === null) {
            $settings = (new Application())->loadSettings(true);

            $config = Setup::createAnnotationMetadataConfiguration(
                $settings['config']['doctrine']['meta']['entity_path'],
                $settings['config']['doctrine']['meta']['dev_mode'],
                $settings['config']['doctrine']['meta']['proxy_dir']
            );

            self::$em = EntityManager::create($settings['config']['doctrine']['connection'], $config);
        }

        return self::$em;
    }

    public function updateDbSchema()
    {
        $em = self::getEm();

        $classes = [];
        foreach ($this->entities as $entity) {
            $classes[] = $em->getClassMetadata($entity);
        }

        $tool = new SchemaTool($em);
        $tool->updateSchema($classes);
    }

    public function emptyDb()
    {
        $em = self::getEm();
        $connection = $em->getConnection();

        foreach ($this->entities as $entity) {
            $class = $em->getClassMetadata($entity);
            $connection->query('DELETE FROM ' . $class->getTableName());
        }
    }

    /**
     *
     * @param array $roles
     * @return \Brave\Core\Entity\Role[]
     */
    public function addRoles($roles)
    {
        $em = $this->getEm();

        $roleEntities = [];
        foreach ($roles as $roleName) {
            $role = new Role();
            $role->setName($roleName);
            $em->persist($role);
            $roleEntities[] = $role;
        }
        $em->flush();

        return $roleEntities;
    }

    /**
     *
     * @param string $name
     * @param int $charId
     * @param array $roles
     * @return number
     */
    public function addUser($name, $charId, $roles)
    {
        $em = $this->getEm();

        $user = new User();
        $user->setCharacterId($charId);
        $user->setName($name);
        $em->persist($user);

        foreach ($this->addRoles($roles) as $role) {
            $user->addRole($role);
        }

        $em->flush();

        return $user->getId();
    }

    /**
     *
     * @param string $name
     * @param string $secret
     * @param array $roles
     * @return number
     */
    public function addApp($name, $secret, $roles)
    {
        $em = $this->getEm();

        $app = new App();
        $app->setName($name);
        $app->setSecret(password_hash($secret, PASSWORD_DEFAULT));
        $em->persist($app);

        foreach ($this->addRoles($roles) as $role) {
            $app->addRole($role);
        }

        $em->flush();

        return $app->getId();
    }
}
