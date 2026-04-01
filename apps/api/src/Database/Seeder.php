<?php

declare(strict_types=1);

namespace Guard51\Database;

use Doctrine\ORM\EntityManagerInterface;
use Guard51\Entity\AuditLog;
use Guard51\Entity\PlatformBankAccount;
use Guard51\Entity\Tenant;
use Guard51\Entity\TenantBankAccount;
use Guard51\Entity\TenantStatus;
use Guard51\Entity\TenantType;
use Guard51\Entity\User;
use Guard51\Entity\UserRole;

/**
 * Seeds the database with essential data for development and testing.
 * Idempotent: checks for existing data before inserting.
 */
class Seeder
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function run(): void
    {
        echo "🌱 Guard51 Database Seeder\n";
        echo "──────────────────────────\n\n";

        $this->seedSuperAdmin();
        $this->seedPlatformBankAccount();
        $this->seedDemoSecurityCompany();
        $this->seedDemoNeighborhoodWatch();

        $this->em->flush();
        echo "\n✅ Seeding complete.\n";
    }

    private function seedSuperAdmin(): void
    {
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@guard51.com']);
        if ($existing) {
            echo "  ⏭️  Super admin already exists.\n";
            return;
        }

        $user = new User();
        $user->setEmail('admin@guard51.com')
            ->setPassword('Guard51@Admin2026')
            ->setFirstName('Guard51')
            ->setLastName('Admin')
            ->setPhone('+2348000000001')
            ->setRole(UserRole::SUPER_ADMIN)
            ->setIsActive(true);
        $user->verifyEmail();

        // Super admin has no tenant_id (platform-level)
        $this->em->persist($user);
        echo "  ✅ Super admin created: admin@guard51.com\n";

        // Audit log
        $audit = new AuditLog();
        $audit->setTenantId('system')
            ->setAction(\Guard51\Entity\AuditAction::CREATE)
            ->setResourceType('User')
            ->setResourceId($user->getId())
            ->setDescription('Super admin seeded during initial setup');
        $this->em->persist($audit);
    }

    private function seedPlatformBankAccount(): void
    {
        $existing = $this->em->getRepository(PlatformBankAccount::class)->findAll();
        if (!empty($existing)) {
            echo "  ⏭️  Platform bank account already exists.\n";
            return;
        }

        $account = new PlatformBankAccount();
        $account->setBankName('Guaranty Trust Bank')
            ->setAccountNumber('0123456789')
            ->setAccountName('DOSTHQ Limited')
            ->setBankCode('058')
            ->setIsPrimary(true)
            ->setIsActive(true);

        $this->em->persist($account);
        echo "  ✅ Platform bank account created: GTBank - DOSTHQ Limited\n";
    }

    private function seedDemoSecurityCompany(): void
    {
        $existing = $this->em->getRepository(Tenant::class)->findOneBy(['email' => 'info@shieldforce.demo']);
        if ($existing) {
            echo "  ⏭️  Demo security company already exists.\n";
            return;
        }

        // Create tenant
        $tenant = new Tenant();
        $tenant->setName('ShieldForce Security Services')
            ->setTenantType(TenantType::PRIVATE_SECURITY)
            ->setRcNumber('RC-1234567')
            ->setEmail('info@shieldforce.demo')
            ->setPhone('+2348012345678')
            ->setAddress('15 Admiralty Way, Lekki Phase 1')
            ->setCity('Lagos')
            ->setState('Lagos')
            ->setCountry('Nigeria')
            ->setStatus(TenantStatus::ACTIVE)
            ->setBranding([
                'primary_color' => '#1B3A5C',
                'secondary_color' => '#E8792D',
            ]);
        $tenant->markOnboarded();

        $this->em->persist($tenant);
        echo "  ✅ Demo tenant created: ShieldForce Security Services\n";

        // Create company admin
        $admin = new User();
        $admin->setEmail('admin@shieldforce.demo')
            ->setPassword('ShieldForce@2026')
            ->setFirstName('Adebayo')
            ->setLastName('Okonkwo')
            ->setPhone('+2348012345679')
            ->setRole(UserRole::COMPANY_ADMIN)
            ->setTenantId($tenant->getId())
            ->setIsActive(true);
        $admin->verifyEmail();

        $this->em->persist($admin);
        echo "  ✅ Company admin created: admin@shieldforce.demo\n";

        // Create supervisor
        $supervisor = new User();
        $supervisor->setEmail('supervisor@shieldforce.demo')
            ->setPassword('ShieldForce@2026')
            ->setFirstName('Chika')
            ->setLastName('Nwosu')
            ->setPhone('+2348012345680')
            ->setRole(UserRole::SUPERVISOR)
            ->setTenantId($tenant->getId())
            ->setIsActive(true);
        $supervisor->verifyEmail();

        $this->em->persist($supervisor);
        echo "  ✅ Supervisor created: supervisor@shieldforce.demo\n";

        // Create guard
        $guard = new User();
        $guard->setEmail('guard@shieldforce.demo')
            ->setPassword('ShieldForce@2026')
            ->setFirstName('Musa')
            ->setLastName('Ibrahim')
            ->setPhone('+2348012345681')
            ->setRole(UserRole::GUARD)
            ->setTenantId($tenant->getId())
            ->setIsActive(true);
        $guard->verifyEmail();

        $this->em->persist($guard);
        echo "  ✅ Guard created: guard@shieldforce.demo\n";

        // Create dispatcher
        $dispatcher = new User();
        $dispatcher->setEmail('dispatch@shieldforce.demo')
            ->setPassword('ShieldForce@2026')
            ->setFirstName('Funmi')
            ->setLastName('Adeyemi')
            ->setPhone('+2348012345682')
            ->setRole(UserRole::DISPATCHER)
            ->setTenantId($tenant->getId())
            ->setIsActive(true);
        $dispatcher->verifyEmail();

        $this->em->persist($dispatcher);
        echo "  ✅ Dispatcher created: dispatch@shieldforce.demo\n";

        // Create tenant bank account
        $bankAccount = new TenantBankAccount();
        $bankAccount->setTenantId($tenant->getId())
            ->setBankName('First Bank of Nigeria')
            ->setAccountNumber('3012345678')
            ->setAccountName('ShieldForce Security Services Ltd')
            ->setBankCode('011')
            ->setIsPrimary(true)
            ->setIsActive(true);

        $this->em->persist($bankAccount);
        echo "  ✅ Tenant bank account created: First Bank - ShieldForce\n";

        // ── Demo Clients ──
        $clientEntity = new \Guard51\Entity\Client();
        $clientEntity->setTenantId($tenant->getId())->setCompanyName('Zenith Tower Properties')
            ->setContactName('Tunde Bakare')->setContactEmail('tunde@zenithtower.ng')
            ->setContactPhone('+2348033001122')->setCity('Lagos')->setState('Lagos')
            ->setAddress('24 Marina Road, Lagos Island')->setBillingType(\Guard51\Entity\BillingType::MONTHLY)
            ->setBillingRate(450000)->setContractStart(new \DateTimeImmutable('2026-01-01'))
            ->setContractEnd(new \DateTimeImmutable('2026-12-31'));
        $this->em->persist($clientEntity);

        $client2 = new \Guard51\Entity\Client();
        $client2->setTenantId($tenant->getId())->setCompanyName('ShopRite Ikeja Mall')
            ->setContactName('Ngozi Okeke')->setContactEmail('ngozi@shoprite.ng')
            ->setContactPhone('+2348044002233')->setCity('Lagos')->setState('Lagos')
            ->setBillingType(\Guard51\Entity\BillingType::PER_GUARD)->setBillingRate(180000);
        $this->em->persist($client2);
        echo "  ✅ 2 demo clients created\n";

        // ── Demo Sites ──
        $site1 = new \Guard51\Entity\Site();
        $site1->setTenantId($tenant->getId())->setName('Lekki Phase 1 HQ')->setAddress('15 Admiralty Way')
            ->setCity('Lagos')->setState('Lagos')->setLatitude('6.4541')->setLongitude('3.4747')
            ->setGeofenceRadius(150)->setContactName('Tunde Bakare')->setContactPhone('+2348033001122')
            ->setClientId($clientEntity->getId());
        $this->em->persist($site1);

        $site2 = new \Guard51\Entity\Site();
        $site2->setTenantId($tenant->getId())->setName('Victoria Island Branch')->setAddress('Plot 42, Adeola Odeku')
            ->setCity('Lagos')->setState('Lagos')->setLatitude('6.4281')->setLongitude('3.4219')
            ->setGeofenceRadius(100)->setClientId($clientEntity->getId());
        $this->em->persist($site2);

        $site3 = new \Guard51\Entity\Site();
        $site3->setTenantId($tenant->getId())->setName('Ikeja Mall Post')->setAddress('Alausa, Ikeja')
            ->setCity('Lagos')->setState('Lagos')->setLatitude('6.6018')->setLongitude('3.3515')
            ->setGeofenceRadius(200)->setClientId($client2->getId());
        $this->em->persist($site3);
        echo "  ✅ 3 demo sites created\n";

        // ── Demo Guards ──
        $guardNames = [
            ['Musa', 'Ibrahim', '+2349011001100', 'GRD-0001'],
            ['Kelechi', 'Eze', '+2349011002200', 'GRD-0002'],
            ['Adamu', 'Yusuf', '+2349011003300', 'GRD-0003'],
            ['Blessing', 'Okafor', '+2349011004400', 'GRD-0004'],
            ['Emeka', 'Nwankwo', '+2349011005500', 'GRD-0005'],
        ];
        foreach ($guardNames as $gn) {
            $g = new \Guard51\Entity\Guard();
            $g->setTenantId($tenant->getId())->setFirstName($gn[0])->setLastName($gn[1])
                ->setPhone($gn[2])->setEmployeeNumber($gn[3])
                ->setHireDate(new \DateTimeImmutable('2026-01-15'))
                ->setPayType(\Guard51\Entity\PayType::MONTHLY)->setPayRate(65000)
                ->setBankName('Access Bank')->setBankAccountNumber('001234567' . substr($gn[3], -1))
                ->setBankAccountName($gn[0] . ' ' . $gn[1])
                ->setState('Lagos');
            $this->em->persist($g);
        }
        echo "  ✅ 5 demo guards created\n";

        // Audit log
        $this->em->persist((new AuditLog())
            ->setTenantId('system')
            ->setAction(\Guard51\Entity\AuditAction::CREATE)
            ->setResourceType('Tenant')
            ->setResourceId($tenant->getId())
            ->setDescription('Demo private security company seeded'));
    }

    private function seedDemoNeighborhoodWatch(): void
    {
        $existing = $this->em->getRepository(Tenant::class)->findOneBy(['email' => 'info@ikeja-watch.demo']);
        if ($existing) {
            echo "  ⏭️  Demo neighborhood watch already exists.\n";
            return;
        }

        // Create GOV tenant
        $tenant = new Tenant();
        $tenant->setName('Ikeja Community Neighborhood Watch')
            ->setTenantType(TenantType::NEIGHBORHOOD_WATCH)
            ->setEmail('info@ikeja-watch.demo')
            ->setPhone('+2348098765432')
            ->setAddress('12 Obafemi Awolowo Way, Ikeja')
            ->setCity('Lagos')
            ->setState('Lagos')
            ->setCountry('Nigeria')
            ->setStatus(TenantStatus::ACTIVE)
            ->setOrgSubtype('lga_chapter')
            ->setBranding([
                'primary_color' => '#2D6A2E',
                'secondary_color' => '#F5A623',
            ]);
        $tenant->markOnboarded();

        $this->em->persist($tenant);
        echo "  ✅ Demo GOV tenant created: Ikeja Neighborhood Watch\n";

        // Create coordinator (company admin equivalent)
        $coordinator = new User();
        $coordinator->setEmail('coordinator@ikeja-watch.demo')
            ->setPassword('IkejaWatch@2026')
            ->setFirstName('Bola')
            ->setLastName('Tinubu-Adesanya')
            ->setPhone('+2348098765433')
            ->setRole(UserRole::COMPANY_ADMIN)
            ->setTenantId($tenant->getId())
            ->setIsActive(true);
        $coordinator->verifyEmail();

        $this->em->persist($coordinator);
        echo "  ✅ GOV coordinator created: coordinator@ikeja-watch.demo\n";

        // Audit log
        $this->em->persist((new AuditLog())
            ->setTenantId('system')
            ->setAction(\Guard51\Entity\AuditAction::CREATE)
            ->setResourceType('Tenant')
            ->setResourceId($tenant->getId())
            ->setDescription('Demo neighborhood watch seeded (GOV tenant)'));
    }
}
