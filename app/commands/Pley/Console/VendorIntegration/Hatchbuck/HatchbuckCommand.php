<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\VendorIntegration\Hatchbuck;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>HatchbuckCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class HatchbuckCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait,
        NewUserHandlingTrait,
        MemberHandlingTrait,
        CancelledMemberHandlingTrait,
        PastDueMemberHandlingTrait,
        InvitedFriendHandlingTrait,
        SubscriptionTagHandlingTrait,
        RevealHandlingTrait;
    
    protected static $NEW_USERS_THRESHOLD_TIME = 300;  // 5*60; - 5min
    protected static $CHECK_SPAN_TIME          = 4200; // (1*60*60) + (10*60); - 1Hr and 10mins
    
    protected static $HATCHBUCK_CONTACT_STATUS_MAP = [
        'user'      => 'emNFZFhtcjQ1STQzM3FMVFB2ZjUwVkxNMUxpbnJEWHhGUTVTZ0YwM0gtVTE1', // Prospect
        'member'    => 'SkVtVDNqMVVjd2VUMURmYVNBTEg0eE1zbHV2d21lN3A0MGl4Y29WV3ZXYzE1', // Customer
        'cancelled' => 'WUQ5a0xsWlloZWZQdzQyQi1rWHhsTGRodUl1eDJ3VnAzUU5vRW9XLTRTNDE1', // Former Customer
        'referral'  => 'emNFZFhtcjQ1STQzM3FMVFB2ZjUwVkxNMUxpbnJEWHhGUTVTZ0YwM0gtVTE1', // Prospect
    ];
    protected static $HATCHBUCK_TAG_MAP = [
        'cancelled'         => 'WmVqWERGd3hucl9UT0tfZFNVYmxkZXQwWFdneVhrWk9OSFZpaWpSdElTWTE1', // Pleybox: Cancelled
        'invite'            => 'eWJ3Slk0dlluZFVjbE1qNEdMVmF2bjdfaFd6TlZROURvQTJIRWRKWVdBUTE1', // Pleybox: Invite
        'member'            => 'bkJ3aktPbk9PQU15Y2k0ZmE0NDE3N2VZXzQ5WWtHLVFCZmhJRGwzaFN2MDE1', // Pleybox: Member
        'paused'            => 'c3N2Y2RHWEk3R2F6dTQyQnVfbW9uWTNtbktGbE5JckhSRXhMUkt0T2UwazE1', // member - paused
        'user'              => 'SExWMlN2VExkVDZBZmtCRzNzNkFNaFQ5WjdObGU0ODdmeVNqNmQxU3F6MDE1', // Pleybox: User
        'pleybox'           => 'bDZiNEJDNm5oN1MzbkJlY3N5bGdPeC1OYTI3a0FEZWttdWtJeDhUMDVNSTE1', // Pleybox
        'toyLibrary'        => 'T3A3dEFiVkpsUmFjYW5qUGpzMmNaSDRheG5DdF81NnZlVVdfX1VoRU92azE1', // Toy Library
        'pastDue'           => 'QVo0TExUbjkwLVZYNlpGM2VoeFF0RXVYcnpzbFdDa2VJbFdMTkNVSkFIRTE1', // Source - Past Due
        'sourceFooter'      => 'bWtJZnJvUXlQTDJHUTFhMGhKamZzeEc0ZFRpWHJ6cF9leDZMM3NlRWZidzE1', // Source - Footer
        'sourcePopup'       => 'SENzSUpXWnY0Y2o3ZWNrQ2VsTkNUa3phQUFWdUYyblMxdnhDQ1pTeVpXazE1', // Source - Popup
        
        // If a new LEAD Tag is added, for a new subscription, make sure that the respective
        // entry is updated on the `NewUserHandlingTrait` file for correct mapping.
        'tbNewLeadPrincess' => 'aWpqNTBCbTI3OURSODRILXRjYXBRVmhIX09uNkxLY2FUZ1k2NDNKSVNJdzE1', // Pleybox: New Lead - Disney Princess
        'tbNewLeadNatGeo'   => 'ZjEwZFd6N1BmQ3NOOHlOTVFDa1BCcEhPWWd0T05MY0gweGVwN0xfSkZvWTE1', // Pleybox: New Lead - National Geographic
        'tbNewLeadHotWheels'   => 'SVpjdEdpYkJWX191S09ISTljMGxtT3Fnd1Q4TVlUY3lRR1dUMC1SWFVzYzE1', // Pleybox: New Lead - Hot Wheels

        // Tags for disney princess reveal notifications
        'tbPrincessReveal' => 'aFJFbjIwZ2txcjJMRXpiQTE4NEZVcnpkYjVUQ0VTbmpET2ZMV2J2XzJjODE1'
    ];
    
    protected static $HATCHBUCK_SUBSCRIPTION_TAG_MAP = [
        1 => 'SG1YeGZ6YjFEeXc1dmdXUEFPWUM2NzFRMGFURnZCQ1J1dmVxRzdOclIwUTE1', // Subscription - Disney Princess
        2 => 'Zjljb0RtaGtOZHpOa0pnOFBPdnQ5MWFzQzBLbHpUWXF4MENnZE85TmhTSTE1', // Subscription - National Geographic
        3 => 'eTRCMlR5eC11V213ZlRVNG9ZeXhoXzlxU1ppYi1WaVJBSExNZXUzeXBRczE1', // Subscription - Hot Wheels
//        y => 'UzExZUwxc2NQRmpqWVpWRVE3S1FtOEJmOUJNMlROQklBdzFRc1JkdFBfODE1', // Subscription - Junior Explorer
//        z => 'dktXckNDRGRsZm1UTUJZemFZUkNSNlFiRkdIMGhTVWJHYXBlanFRM28zazE1', // Subscription - Star Wars
    ];
    
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:hatchbuckSync';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to Sync Users into Hatchbuck CRM';
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Hatchbuck\Hatchbuck */
    protected $_hatchbuck;
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        
        $this->_hatchbuck = new \Hatchbuck\Hatchbuck();
        
        $this->_setLogOutput(true);
    }
    
    public function fire()
    {
        $startTime = microtime(true);
        
        $this->line('----------------------------------------------------------------------------');
        $this->line('Hatchbuck syncing ...');
        
        $this->_NewUser_processor();            // Comes from NewUserHandlingTrait
        $this->_Member_processor();             // Comes from MemberHandlingTrait
        $this->_CancelledMember_processor();    // Comes from CancelledMemberHandlingTrait
        $this->_PastDueMember_processor();      // Comes from PastDueMemberHandlingTrait
        $this->_Invite_processor();             // Comes from InvitedFriendHandlingTrait
        $this->_SubscriptionTag_processor();    // Comes from SubscriptionTagHandlingTrait
        $this->_Reveal_processor();             // Comes from SubscriptionTagHandlingTrait

        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line(sprintf('Total Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /**
     * Returns the Hatchbuck contact if found by the supplied email.
     * @param string $userEmail
     * @return \Hatchbuck\Entity\Contact
     */
    protected function _getHatchbuckContact($userEmail)
    {
        $searchInput = new \Hatchbuck\SearchInput();
        $searchInput->addEmail($userEmail);
        
        $contactList = $this->_hatchbuck->search($searchInput);
        
        if (empty($contactList)) {
            return null;
        }
        
        return $contactList[0];
    }
    
    
    
}
