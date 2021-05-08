<?php

declare(strict_types=1);

namespace Neucore\Repository;

use Doctrine\ORM\EntityRepository;
use Neucore\Entity\Character;

/**
 * CharacterRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @method Character|null find($id, $lockMode = null, $lockVersion = null)
 * @method Character|null findOneBy(array $criteria, array $orderBy = null)
 * @method Character[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CharacterRepository extends EntityRepository
{
    /**
     * @return Character[]
     */
    public function findMainByNamePartialMatch(string $name): array
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.name LIKE :name')
            ->addOrderBy('c.name', 'ASC')
            ->setParameter('name', "%$name%")
            ->andWhere('c.main = :main')
            ->setParameter('main', true);

        return $query->getQuery()->getResult();
    }

    /**
     * @return int[] Character IDs
     */
    public function getGroupMembersMainCharacter(int $groupId): array
    {
        $query = $this->createQueryBuilder('c')
            ->select('c.id')
            ->leftJoin('c.player', 'p')
            ->innerJoin('p.groups', 'g', 'WITH', 'g.id = :groupId')
            ->where('c.main = :main')
            ->setParameter('groupId', $groupId)
            ->setParameter('main', true);

        return array_map(function (array $char) {
            return (int) $char['id'];
        }, $query->getQuery()->getResult());
    }

    /**
     * @param int[] $playerIds
     */
    public function getAllCharactersFromPlayers(array $playerIds): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select(
            'c.id',
            'IDENTITY(c.player) AS playerId'
        )
            ->where($qb->expr()->in('c.player', ':ids'))
            ->setParameter('ids', $playerIds);

        return array_map(function (array $row) {
            return [
                'id' => (int) $row['id'],
                'playerId' => (int) $row['playerId'],
            ];
        }, $qb->getQuery()->getResult());
    }
}
