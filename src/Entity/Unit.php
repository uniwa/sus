<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Περιέχει πληροφορίες σχετικά με τις Μονάδες. (old `SUS\SiteBundle\Entity\Unit`)
 *
 * Legacy column comments (annotation-only, absent from the real DB, omitted from the mapping
 * so the schema diff stays clean — see docs/port-inventory/entities.md §0.7/§2.1):
 *  - unit_id:         "Ο Κωδικός ID της Μονάδας."
 *  - registry_no:     "Ο Κωδικός ΥΠΕΠΘ της Μονάδας."
 *  - name:            "Το όνομα της Μονάδας."
 *  - special_name:    "Το Προσωνύμιο της Μονάδας."
 *  - street_address:  "Η Διεύθυνση της Μονάδας."
 *  - postal_code:     "Ο Ταχυδρομικός Κώδικας της Μονάδας."
 *  - fax_number:      "Ο Αριθμός Τηλεομοιοτυπίας (φαξ) της Μονάδας."
 *  - phone_number:    "Ο Τηλεφωνικός Αρθμός της Μονάδας."
 *  - email:           "Το Ηλεκτρονικό Ταχυδρομέιο της Μονάδας."
 *  - tax_number:      "Ο Αριθμός Φορολογικού Μητρώου της Μονάδας."
 *  - comments:        "Παρατηρήσεις - Σχόλια σχετικά με την Μονάδα."
 *  - positioning:     "Η Κτηριακή Θέση της Μονάδας."
 *  - website:         "Η Ιστοσελίδα της Μονάδας."
 *  - foundation_date: "Η Χρονιά Δημιουργίας της Μονάδας."
 *  - deletedAt:       "Η Ημερομηνία Διαγραφής της Μονάδας."
 *
 * All index/unique names below are the EXACT legacy names from the real DB (entities.md §2.1/§3.1).
 * Timestampable fields are declared directly (not via the Gedmo trait) so the camelCase column
 * names `createdAt`/`updatedAt` survive the underscore naming strategy (§1.2).
 */
#[ORM\Entity]
#[ORM\Table(name: 'units')]
#[ORM\UniqueConstraint(name: 'UNIQ_E9B07449942448A2', columns: ['registry_no'])]
#[ORM\Index(name: 'fk_units_region_edu_admins_idx', columns: ['region_edu_admin_id'])]
#[ORM\Index(name: 'fk_units_prefectures_idx', columns: ['prefecture_id'])]
#[ORM\Index(name: 'fk_units_edu_admins_idx', columns: ['edu_admin_id'])]
#[ORM\Index(name: 'fk_units_municipalities_idx', columns: ['municipality_id'])]
#[ORM\Index(name: 'fk_units_types_idx', columns: ['unit_type_id'])]
#[ORM\Index(name: 'fk_units_categories1_idx', columns: ['category_id'])]
#[ORM\Index(name: 'fk_units_states1_idx', columns: ['state_id'])]
#[ORM\Index(name: 'fk_units_doy1_idx', columns: ['tax_office_id'])]
#[ORM\Index(name: 'IDX_E9B074499FE74F3C', columns: ['implementation_entity_id'])]
#[ORM\Index(name: 'legal_character_id', columns: ['legal_character_id'])]
#[ORM\Index(name: 'municipality_community_id', columns: ['municipality_community_id'])]
#[ORM\Index(name: 'region_id', columns: ['region_id'])]
#[ORM\Index(name: 'country', columns: ['country'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Unit extends MMSyncableEntity
{
    #[ORM\Id]
    #[ORM\Column(name: 'unit_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $unitId = null;

    #[ORM\Column(name: 'registry_no', type: 'string', length: 50, nullable: true)]
    private ?string $registryNo = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: 'special_name', type: 'string', length: 255, nullable: true)]
    private ?string $specialName = null;

    #[ORM\Column(name: 'street_address', type: 'string', length: 255, nullable: true)]
    private ?string $streetAddress = null;

    #[ORM\Column(name: 'postal_code', type: 'integer', nullable: true)]
    private ?int $postalCode = null;

    #[ORM\Column(name: 'fax_number', type: 'string', length: 255, nullable: true)]
    private ?string $faxNumber = null;

    #[ORM\Column(name: 'phone_number', type: 'string', length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'tax_number', type: 'string', length: 255, nullable: true)]
    private ?string $taxNumber = null;

    #[ORM\Column(name: 'comments', type: 'string', length: 255, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(name: 'positioning', type: 'string', length: 255, nullable: true)]
    private ?string $positioning = null;

    #[ORM\Column(name: 'website', type: 'string', length: 255, nullable: true)]
    private ?string $website = null;

    /** MySQL/MariaDB YEAR column — custom DBAL type, see App\Doctrine\Types\YearType. */
    #[ORM\Column(name: 'foundation_date', type: 'year', nullable: true)]
    private ?string $foundationDate = null;

    #[ORM\ManyToOne(targetEntity: TaxOffice::class)]
    #[ORM\JoinColumn(name: 'tax_office_id', referencedColumnName: 'tax_office_id')]
    private ?TaxOffice $taxOffice = null;

    #[ORM\ManyToOne(targetEntity: EduAdmin::class)]
    #[ORM\JoinColumn(name: 'edu_admin_id', referencedColumnName: 'edu_admin_id')]
    private ?EduAdmin $eduAdmin = null;

    #[ORM\ManyToOne(targetEntity: RegionEduAdmin::class)]
    #[ORM\JoinColumn(name: 'region_edu_admin_id', referencedColumnName: 'region_edu_admin_id')]
    private ?RegionEduAdmin $regionEduAdmin = null;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'region_id')]
    private ?Region $region = null;

    #[ORM\ManyToOne(targetEntity: ImplementationEntity::class)]
    #[ORM\JoinColumn(name: 'implementation_entity_id', referencedColumnName: 'implementation_entity_id')]
    private ?ImplementationEntity $implementationEntity = null;

    #[ORM\ManyToOne(targetEntity: UnitType::class)]
    #[ORM\JoinColumn(name: 'unit_type_id', referencedColumnName: 'unit_type_id')]
    private ?UnitType $unitType = null;

    #[ORM\ManyToOne(targetEntity: Prefecture::class)]
    #[ORM\JoinColumn(name: 'prefecture_id', referencedColumnName: 'prefecture_id')]
    private ?Prefecture $prefecture = null;

    #[ORM\ManyToOne(targetEntity: State::class)]
    #[ORM\JoinColumn(name: 'state_id', referencedColumnName: 'state_id')]
    private ?State $state = null;

    #[ORM\ManyToOne(targetEntity: LegalCharacter::class)]
    #[ORM\JoinColumn(name: 'legal_character_id', referencedColumnName: 'legal_character_id')]
    private ?LegalCharacter $legalCharacter = null;

    #[ORM\ManyToOne(targetEntity: UnitCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'category_id')]
    private ?UnitCategory $category = null;

    #[ORM\ManyToOne(targetEntity: Municipality::class)]
    #[ORM\JoinColumn(name: 'municipality_id', referencedColumnName: 'municipality_id')]
    private ?Municipality $municipality = null;

    /**
     * The empty `comment` option is deliberate: SchemaTool copies the referenced column's
     * DB comment (`municipality_communities` is the only table with real comments) onto the
     * join column, but the real `units.municipality_community_id` column has NO comment.
     */
    #[ORM\ManyToOne(targetEntity: MunicipalityCommunity::class)]
    #[ORM\JoinColumn(name: 'municipality_community_id', referencedColumnName: 'municipality_community_id', options: ['comment' => ''])]
    private ?MunicipalityCommunity $municipalityCommunity = null;

    #[ORM\Column(name: 'latitude', type: 'string', length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(name: 'longitude', type: 'string', length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'country', type: 'string', length: 2, nullable: true)]
    private ?string $country = 'GR';

    /** Ο υπεύθυνος (manager) της μονάδας — inverse side, owning side is Worker::$unit. */
    #[ORM\OneToOne(targetEntity: Worker::class, mappedBy: 'unit', cascade: ['persist'])]
    private ?Worker $manager = null;

    /** @var Collection<int, Worker> */
    #[ORM\ManyToMany(targetEntity: Worker::class, inversedBy: 'responsibleUnits', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'workers_responsibles')]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'unit_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'worker_id', referencedColumnName: 'worker_id', onDelete: 'CASCADE')]
    private Collection $responsibles;

    #[ORM\Column(name: 'deletedAt', type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updatedAt', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    protected ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->responsibles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->getUnitId();
    }

    public function getUnitId(): ?int
    {
        return $this->unitId;
    }

    public function setUnitId(?int $unitId): void
    {
        $this->unitId = $unitId;
    }

    public function getRegistryNo(): ?string
    {
        return $this->registryNo;
    }

    public function setRegistryNo(?string $registryNo): void
    {
        $this->registryNo = $registryNo;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSpecialName(): ?string
    {
        return $this->specialName;
    }

    public function setSpecialName(?string $specialName): void
    {
        $this->specialName = $specialName;
    }

    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    public function setStreetAddress(?string $streetAddress): void
    {
        $this->streetAddress = $streetAddress;
    }

    public function getPostalCode(): ?int
    {
        return $this->postalCode;
    }

    public function setPostalCode(?int $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    public function setFaxNumber(?string $faxNumber): void
    {
        $this->faxNumber = $faxNumber;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(?string $taxNumber): void
    {
        $this->taxNumber = $taxNumber;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
    }

    public function getPositioning(): ?string
    {
        return $this->positioning;
    }

    public function setPositioning(?string $positioning): void
    {
        $this->positioning = $positioning;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getFoundationDate(): ?string
    {
        return $this->foundationDate;
    }

    public function setFoundationDate(?string $foundationDate = null): void
    {
        $this->foundationDate = $foundationDate;
    }

    public function getTaxOffice(): ?TaxOffice
    {
        return $this->taxOffice;
    }

    public function setTaxOffice(?TaxOffice $taxOffice = null): void
    {
        $this->taxOffice = $taxOffice;
    }

    public function getEduAdmin(): ?EduAdmin
    {
        return $this->eduAdmin;
    }

    public function setEduAdmin(?EduAdmin $eduAdmin = null): void
    {
        $this->eduAdmin = $eduAdmin;
    }

    public function getRegionEduAdmin(): ?RegionEduAdmin
    {
        return $this->regionEduAdmin;
    }

    public function setRegionEduAdmin(?RegionEduAdmin $regionEduAdmin = null): void
    {
        $this->regionEduAdmin = $regionEduAdmin;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region = null): void
    {
        $this->region = $region;
    }

    public function getImplementationEntity(): ?ImplementationEntity
    {
        return $this->implementationEntity;
    }

    public function setImplementationEntity(?ImplementationEntity $implementationEntity = null): void
    {
        $this->implementationEntity = $implementationEntity;
    }

    public function getUnitType(): ?UnitType
    {
        return $this->unitType;
    }

    public function setUnitType(?UnitType $unitType = null): void
    {
        $this->unitType = $unitType;
    }

    public function getPrefecture(): ?Prefecture
    {
        return $this->prefecture;
    }

    public function setPrefecture(?Prefecture $prefecture = null): void
    {
        $this->prefecture = $prefecture;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state = null): void
    {
        $this->state = $state;
    }

    public function getLegalCharacter(): ?LegalCharacter
    {
        return $this->legalCharacter;
    }

    public function setLegalCharacter(?LegalCharacter $legalCharacter = null): void
    {
        $this->legalCharacter = $legalCharacter;
    }

    public function getCategory(): ?UnitCategory
    {
        return $this->category;
    }

    public function setCategory(?UnitCategory $category = null): void
    {
        $this->category = $category;
    }

    public function getMunicipality(): ?Municipality
    {
        return $this->municipality;
    }

    public function setMunicipality(?Municipality $municipality = null): void
    {
        $this->municipality = $municipality;
    }

    public function getMunicipalityCommunity(): ?MunicipalityCommunity
    {
        return $this->municipalityCommunity;
    }

    public function setMunicipalityCommunity(?MunicipalityCommunity $municipalityCommunity = null): void
    {
        $this->municipalityCommunity = $municipalityCommunity;
    }

    /**
     * Old Sonata form quirk kept on purpose: lazily instantiate an empty Worker bound to this
     * unit so the admin "manager" sub-form always has an object to write into.
     */
    public function getManager(): Worker
    {
        if (!isset($this->manager)) {
            $this->manager = new Worker();
            $this->manager->setUnit($this);
        }

        return $this->manager;
    }

    public function setManager(?Worker $manager = null): void
    {
        $this->manager = $manager;
    }

    /** @return Collection<int, Worker> */
    public function getResponsibles(): Collection
    {
        return $this->responsibles;
    }

    /** @param iterable<Worker>|null $responsibles */
    public function setResponsibles($responsibles = null): void
    {
        $collection = new ArrayCollection();
        foreach ($responsibles ?? [] as $responsible) {
            $collection->add($responsible);
        }
        $this->responsibles = $collection;
    }

    public function addResponsible(Worker $worker): void
    {
        if (!$this->responsibles->contains($worker)) {
            $this->responsibles->add($worker);
        }
    }

    public function removeResponsible(Worker $worker): void
    {
        $this->responsibles->removeElement($worker);
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt = null): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isActive(): bool
    {
        return !isset($this->deletedAt);
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    /** @param array{lat: ?string, lng: ?string} $latlng */
    public function setLatLng(array $latlng): static
    {
        $this->setLatitude($latlng['lat']);
        $this->setLongitude($latlng['lng']);

        return $this;
    }

    /** @return array{lat: ?string, lng: ?string} */
    public function getLatLng(): array
    {
        return ['lat' => $this->getLatitude(), 'lng' => $this->getLongitude()];
    }

    public function getMapUrl(): string
    {
        $latLng = $this->getLatLng();

        return 'https://maps.google.com/?q=' . $latLng['lat'] . ',' . $latLng['lng'];
    }

    public function __toString(): string
    {
        return 'SUS: ' . $this->getUnitId() . ' | ΜΜ: ' . $this->getMmSyncId() . ' | ' . $this->getName();
    }
}
