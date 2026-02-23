<?php

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findForActiveGuests(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $ids = $connection->fetchFirstColumn(
            'SELECT id FROM "user" u WHERE NOT ((u.roles::jsonb) @> :admin::jsonb)',
            ['admin' => '["ROLE_ADMIN"]']
        );

        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.id IN (:ids)')
            ->andWhere('u.isActive = true')
            ->setParameter('ids', $ids)
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findGuests(int $limit, int $offset, bool $onlyActive = false): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $ids = $connection->fetchFirstColumn(
            'SELECT id FROM "user" u WHERE NOT ((u.roles::jsonb) @> :admin::jsonb)',
            ['admin' => '["ROLE_ADMIN"]']
        );

        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }


    public function findValidInvitation(string $token, DateTimeImmutable $now):?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.invitationToken = :token')
            ->andWhere('u.invitationExpiredAt IS NOT NULL')
            ->andWhere('u.invitationExpiredAt >= :now')
            ->andWhere('u.isActive = false')
            ->setParameter('token', $token)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
