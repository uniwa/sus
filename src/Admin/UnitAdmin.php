<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Unit;
use App\Form\Type\CountryPickerType;
use App\Form\Type\LatLngType;
use App\Security\RolesRegistry;
use App\Security\UserPermissions;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface as ORMProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Port of `SUS\AdminBundle\Admin\UnitAdmin` (the ONLY Sonata admin of the old app) to
 * Sonata Admin 4 — see docs/port-inventory/admins.md §2.
 *
 * Route names/paths are pinned to the legacy `admin_sus_site_unit_*` / `/admin/sus/site/unit/...`
 * (the post-CAS-login redirect targets `admin_sus_site_unit_list`).
 *
 * The admin `code` is set to `sonata.admin.unit` (the old service id); combined with
 * App\Admin\Security\SusAdminSecurityHandler the permission attributes voted on stay the old
 * plain LIST/VIEW/EDIT/CREATE/EXPORT/DELETE strings decided by App\Security\AdminAclVoter.
 *
 * Deliberate deviations from the old code (inventory-sanctioned):
 *  - the full-text filter uses a bound parameter (the old expr()->literal() was SQL-injectable);
 *  - `latlng` uses App\Form\Type\LatLngType (plain Latitude/Longitude inputs) instead of the
 *    abandoned `oh_google_maps` map picker widget;
 *  - `empty_value` → `placeholder` (modern Symfony forms).
 */
#[AutoconfigureTag(name: 'sonata.admin', attributes: [
    'code' => 'sonata.admin.unit',
    'model_class' => Unit::class,
    'manager_type' => 'orm',
    'group' => 'Μονάδες',
    'label' => 'Μονάδες',
])]
final class UnitAdmin extends AbstractAdmin
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RolesRegistry $rolesRegistry,
        private readonly UserPermissions $userPermissions,
    ) {
    }

    /** Keep the legacy route names (`admin_sus_site_unit_list` etc. — security depends on it). */
    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'admin_sus_site_unit';
    }

    /** Keep the legacy URLs (`/admin/sus/site/unit/list` etc.). */
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'sus/site/unit';
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'ASC';
        $sortValues[DatagridInterface::SORT_BY] = 'unitId';
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        // Deletes are disabled because we don't know how to handle it in MM
        // (old code also removed 'acl' and 'remove', which do not exist in Sonata 4).
        $collection->remove('delete');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Γενικά Στοιχεία')
                ->add('name', null, ['label' => 'Ονομασία'])
                ->add('mmSyncId', null, ['label' => 'Κωδικός ΜΜ'])
                ->add('registryNo', null, ['label' => 'Κωδικός ΥΠΑΙΠΘ'])
                ->add('specialName', null, ['label' => 'Ειδική Ονομασία'])
                ->add('unitType', null, ['label' => 'Τύπος'])
                ->add('foundationDate', null, ['label' => 'Έτος Ίδρυσης'])
                ->add('state', null, ['label' => 'Κατάσταση'])
                ->add('legalCharacter', null, ['label' => 'Νομικός Χαρακτήρας'])
                ->add('eduAdmin', null, ['label' => 'Διεύθυνση Εκπαίδευσης'])
                ->add('regionEduAdmin', null, ['label' => 'Περιφέρειακή Διεύθυνση'])
                ->add('comments', null, ['label' => 'Σχόλια'])
            ->end()
            ->with('Στοιχεία Τοποθεσίας')
                ->add('streetAddress', null, ['label' => 'Οδός, Αριθμός'])
                ->add('postalCode', FieldDescriptionInterface::TYPE_STRING, ['label' => 'Ταχυδρομικός Κώδικας'])
                ->add('municipality', null, ['label' => 'Δήμος ΟΤΑ'])
                ->add('municipalityCommunity', null, ['label' => 'Δημοτική Ενότητα'])
                ->add('prefecture', null, ['label' => 'Περιφερειακή Ενότητα'])
                ->add('region', null, ['label' => 'Περιφέρεια'])
                ->add('positioning', null, ['label' => 'Κτηριακή Θέση'])
                ->add('country', null, ['label' => 'Χώρα'])
            ->end()
            ->with('Στοιχεία Επικοινωνίας')
                ->add('manager.firstName', null, ['label' => 'Όνομα Υπευθύνου'])
                ->add('manager.lastName', null, ['label' => 'Επώνυμο Υπευθύνου'])
                ->add('faxNumber', null, ['label' => 'Αριθμός FAX'])
                ->add('phoneNumber', null, ['label' => 'Τηλέφωνο Επικοινωνίας'])
                ->add('email', null, ['label' => 'E-mail'])
                ->add('website', null, ['label' => 'Website'])
                ->add('mapUrl', FieldDescriptionInterface::TYPE_URL, ['label' => 'Χάρτης'])
            ->end()
        ;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Γενικά Στοιχεία')
                ->add('name', null, ['required' => true, 'label' => 'Ονομασία'])
                ->add('registryNo', null, ['label' => 'Κωδικός ΥΠΑΙΠΘ'])
                ->add('specialName', null, ['label' => ' Ειδική Ονομασία'])
                ->add('unitType', null, ['placeholder' => '-', 'required' => true, 'label' => 'Τύπος'])
                ->add('foundationDate', null, ['label' => 'Έτος Ίδρυσης'])
                ->add('state', null, ['placeholder' => '-', 'required' => true, 'label' => 'Κατάσταση'])
                ->add('legalCharacter', null, ['placeholder' => '-', 'required' => true, 'label' => 'Νομικός Χαρακτήρας'])
                ->add('eduAdmin', null, ['label' => 'Διεύθυνση Εκπαίδευσης', 'query_builder' => static function (EntityRepository $rep) {
                    return $rep->createQueryBuilder('e')->orderBy('e.name', 'ASC');
                }])
                ->add('regionEduAdmin', null, ['label' => 'Περιφερειακή Διεύθυνση', 'query_builder' => static function (EntityRepository $rep) {
                    return $rep->createQueryBuilder('e')->orderBy('e.name', 'ASC');
                }])
                ->add('manager.firstName', TextType::class, ['label' => 'Όνομα Υπευθύνου', 'required' => false])
                ->add('manager.lastName', TextType::class, ['label' => 'Επώνυμο Υπευθύνου', 'required' => false])
                ->add('comments', null, ['label' => 'Σχόλια'])
            ->end()
            ->with('Στοιχεία Τοποθεσίας')
                ->add('streetAddress', null, ['label' => 'Οδός, Αριθμός'])
                ->add('postalCode', null, ['label' => 'Ταχυδρομικός Κώδικας'])
                ->add('municipality', null, ['label' => 'Δήμος ΟΤΑ', 'query_builder' => static function (EntityRepository $rep) {
                    return $rep->createQueryBuilder('e')->orderBy('e.name', 'ASC');
                }])
                ->add('municipalityCommunity', null, ['label' => 'Δημοτική Ενότητα'])
                ->add('prefecture', null, ['label' => 'Περιφερειακή Ενότητα'])
                ->add('region', null, ['label' => 'Περιφέρεια', 'query_builder' => static function (EntityRepository $rep) {
                    return $rep->createQueryBuilder('e')->orderBy('e.name', 'ASC');
                }])
                ->add('positioning', null, ['label' => 'Κτηριακή Θέση'])
                ->add('latlng', LatLngType::class, [
                    'label' => 'Αναζήτηση Συντεταγμένων',
                    'required' => false,
                    'lat_options' => [
                        'label' => 'Latitude',
                        'required' => false,
                        'empty_data' => '0',
                    ],
                    'lng_options' => [
                        'label' => 'Longitude',
                        'required' => false,
                        'empty_data' => '0',
                    ],
                    'default_lat' => 37.984042,
                    'default_lng' => 23.728179,
                ])
                ->add('country', CountryPickerType::class, [
                    'label' => 'Country',
                    'required' => true,
                ])
            ->end()
            ->with('Στοιχεία Επικοινωνίας')
                ->add('faxNumber', null, ['label' => 'Αριθμός FAX'])
                ->add('phoneNumber', null, ['label' => 'Τηλέφωνο Επικοινωνίας'])
                ->add('email', null, ['label' => 'E-mail'])
                ->add('website', null, ['label' => 'Website'])
            ->end()
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            // actions column deliberately FIRST (as in the old app)
            ->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                ],
            ])
            ->add('unitId', FieldDescriptionInterface::TYPE_STRING)
            ->add('mmSyncId', FieldDescriptionInterface::TYPE_STRING)
            ->addIdentifier('name', FieldDescriptionInterface::TYPE_STRING, ['label' => 'Ονομασία'])
            ->add('state.name', FieldDescriptionInterface::TYPE_STRING, ['label' => 'Κατάσταση'])
            ->add('manager', FieldDescriptionInterface::TYPE_STRING, ['label' => 'Υπεύθυνος'])
        ;
    }

    /** Filters exist ONLY for administrators (ROLE_USER4) — all other roles get no filter form. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        if (!$this->isRoleUser4()) {
            return;
        }

        $filter
            ->add('unitId')
            ->add('mmSyncId')
            ->add('name')
            ->add('unitType')
            ->add('manager', null, ['label' => 'Υπεύθυνος'])
            ->add('full_text', CallbackFilter::class, [
                'callback' => $this->getFullTextFilter(...),
                'field_type' => TextType::class,
            ])
        ;
    }

    /**
     * name/mmSyncId LIKE filter. Old behavior preserved, except the value is now a bound
     * parameter (the old expr()->literal('%'.$value.'%') was SQL-injectable).
     */
    public function getFullTextFilter(ORMProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool
    {
        if (!$data->hasValue() || null === $data->getValue() || '' === $data->getValue()) {
            return false;
        }

        $qb = $query->getQueryBuilder();

        // Use `andWhere` instead of `where` to prevent overriding existing `where` conditions
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like($alias.'.name', ':fullTextValue'),
            $qb->expr()->like($alias.'.mmSyncId', ':fullTextValue')
        ));
        $qb->setParameter('fullTextValue', '%'.$data->getValue().'%');

        return true;
    }

    /** ALL batch actions disabled (incl. batch delete), as before. */
    protected function configureBatchActions(array $actions): array
    {
        return [];
    }

    /** The Unit's category is always derived from the selected unitType's category (MM sync). */
    protected function prePersist(object $object): void
    {
        \assert($object instanceof Unit);

        if (null !== $object->getUnitType()) {
            $object->setCategory($object->getUnitType()->getCategoryId());
        }
    }

    protected function preUpdate(object $object): void
    {
        $this->prePersist($object);
    }

    /**
     * Row-level security (the core business rule — old `createQuery`, admins.md §2.9):
     *  - users named in the static roles table (ROLE_USER1/2/4) see units matching their
     *    `unit_types` × `legal_character` (INNER JOIN semantics: units without unitType or
     *    legalCharacter stay invisible — even for Administrators);
     *  - Administrator entries with an empty `unit_types` list mean "all types";
     *  - everyone else (ROLE_USER3, official school accounts) sees exactly the unit whose
     *    `mmSyncId` matches their LDAP `gsnregistrycode`, or gets the legacy Greek exception.
     */
    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        \assert($query instanceof ProxyQuery);

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            // no authenticated context (e.g. console) — nothing to scope by
            return $query;
        }

        $username = $token->getUserIdentifier();
        $qb = $query->getQueryBuilder();
        $rootAlias = current($qb->getRootAliases());

        $entry = $this->rolesRegistry->get($username);
        if (null !== $entry) {
            // USER1 / USER2 / USER4 — scope by unit type × legal character from the roles table
            $legalCharacterName = $entry['legal_character'] ?? [];
            $unitTypeName = $entry['unit_types'] ?? [];
            if (($entry['name'] ?? null) === 'Administrator' && [] === $unitTypeName) {
                $unitTypeName = ['all'];
            }

            $qb->join($rootAlias.'.unitType', 'ut');
            $qb->join($rootAlias.'.legalCharacter', 'lc');
            if ('all' !== ($unitTypeName[0] ?? null)) {
                $qb->andWhere('ut.name IN (:unitTypeName)');
                $qb->setParameter('unitTypeName', $unitTypeName);
            }
            $qb->andWhere('lc.name IN (:legalCharacterName)');
            $qb->setParameter('legalCharacterName', $legalCharacterName);
        } else {
            // USER3 — official school account, sees exactly its own unit
            $mmId = $this->userPermissions->checkPrincipal($username);
            if (null !== $mmId) {
                $qb->andWhere($rootAlias.'.mmSyncId = :mm_id');
                $qb->setParameter('mm_id', $mmId);
            } else {
                throw new \Exception('Δεν βρέθηκε η μονάδα με βάση το uid του χρήστη');
            }
        }

        return $query;
    }

    /**
     * Old check: `$user->hasRole('ROLE_USER4')` on the DB user (whose roles the old voter kept
     * in sync with roles.yml). Roles are now assigned at login; the static table is the
     * authoritative fallback.
     */
    private function isRoleUser4(): bool
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return false;
        }

        return \in_array('ROLE_USER4', $token->getRoleNames(), true)
            || 'ROLE_USER4' === $this->rolesRegistry->getRole($token->getUserIdentifier());
    }
}
