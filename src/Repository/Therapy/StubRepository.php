<?php

namespace App\Repository\Therapy;

use App\DTO\StubObject;
use App\Entity\Therapy\Label;
use App\Entity\Therapy\Stub;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stub|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stub|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stub[]    findAll()
 * @method Stub[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StubRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stub::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Stub $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Stub $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getNewStubObjectFromArray(array $data): Stub
    {
        $stub = new Stub();
        $stub->setName($data['name']);
        $stub->setDescription($data['description']);
        $stub->setExcerpt($data['excerpt']);
        $stub->setBackground($data['background']);

        $this->_em->persist($stub);

        return $this->applyLabels($data['labels'], $stub);
    }

    public function updateEntityFromDto(Stub $stub, StubObject $dto): Stub
    {
        $stub->setName($dto->name);
        $stub->setDescription($dto->description);
        $stub->setExcerpt($dto->excerpt);
        $stub->setBackground($dto->background);

        foreach ($stub->getLabels() as $label) {
            if (!in_array($label->getShortName(), $dto->labels)) {
                $label->removeStub($stub);
                $this->_em->persist($label);
            }
        }

        return $this->applyLabels($dto->labels, $stub);
    }

    public function getStubObjectFromEntity(Stub $stub): StubObject
    {
        $stubDto = new StubObject();
        $stubDto->id = $stub->getId();
        $stubDto->name = $stub->getName();
        $stubDto->excerpt = $stub->getExcerpt();
        $stubDto->description = $stub->getDescription();
        $stubDto->background = $stub->getBackground();

        foreach ($stub->getLabels() as $label) {
            $stubDto->labels[$label->getId()] = $label->getShortName();
        }

        return $stubDto;
    }

    protected function applyLabels($stubLabels, ?Stub $stub = null): Stub
    {
        $allLabels = $this->_em->getRepository(Label::class)->findAll();
        $labelsToInject = [];

        if ($stubLabels instanceof Collection) {
            $tmp = [];
            foreach ($stubLabels as $label) {
                $tmp[] = $label->getShortName();
            }
            $stubLabels = $tmp;
        }

        foreach ($allLabels as $label) {
            foreach ($stubLabels as $_label) {
                if ($label->getShortName() === $_label) {
                    $labelsToInject[$_label] = $label;
                }
            }
        }

        $labelsToCreate = array_diff($stubLabels, array_keys($labelsToInject));

        foreach ($labelsToCreate as $label) {
            $_label = new Label();
            $_label->setShortName($label);
            $_label->setReportName($label);
            $this->_em->persist($_label);

            $labelsToInject[$label] = $_label;
        }

        foreach ($labelsToInject as $label) {
            $label->addStub($stub);
            $this->_em->persist($label);
        }

        $this->_em->flush();

        return $stub;
    }


    public function findSchemesByStubId($stubId)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('s')
            ->from('App\Entity\Therapy\Scheme', 's')
            ->where($qb->expr()->like('s.targets', ':stubId'))
            ->setParameter('stubId', '%stubID=' . $stubId . '%');
        
        return $qb->getQuery()->getResult();
    }
    // /**
    //  * @return Stub[] Returns an array of Stub objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Stub
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
