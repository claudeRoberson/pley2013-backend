<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace operations\v1\Marketing\Referral;

use Pley\Coupon\CouponManager;
use Pley\Db\AbstractDatabaseManager;
use Pley\Enum\InviteEnum;
use Pley\Enum\Referral\RewardEnum;
use Pley\Referral\RewardManager;
use Pley\Repository\Referral\RewardRepository;
use Pley\Repository\User\InviteRepository;
use Pley\Repository\User\UserRepository;
use Pley\Repository\Referral\AcquisitionRepository;
use Pley\Repository\Referral\ProgramRepository;
use Pley\Util\DateTime;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The <kbd>RewardController</kbd> responsible on making CRUD operations on a rewards entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class InviteController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Referral\RewardManager */
    protected $_rewardManager;
    /** @var \Pley\Repository\Referral\RewardRepository */
    protected $_rewardRepository;
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepository;
    /** @var \Pley\Repository\Referral\AcquisitionRepository */
    protected $_acquisitionRepository;
    /** @var \Pley\Repository\Referral\ProgramRepository */
    protected $_referralProgramRepository;
    /** @var \Pley\Repository\User\InviteRepository */
    protected $_inviteRepository;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;


    public function __construct(
        RewardManager $rewardManager,
        RewardRepository $rewardRepository,
        UserRepository $userRepository,
        AcquisitionRepository $acquisitionRepository,
        ProgramRepository $referralProgramRepository,
        InviteRepository $inviteRepository,
        CouponManager $couponManager,
        AbstractDatabaseManager $databaseManager
    ) {
        parent::__construct();
        $this->_rewardManager = $rewardManager;
        $this->_rewardRepository = $rewardRepository;
        $this->_userRepository = $userRepository;
        $this->_acquisitionRepository = $acquisitionRepository;
        $this->_referralProgramRepository = $referralProgramRepository;
        $this->_inviteRepository = $inviteRepository;
        $this->_couponManager = $couponManager;
        $this->_dbManager = $databaseManager;
    }

    // GET /marketing/invites/csv
    public function getInvitesAsCsv()
    {
        \RequestHelper::checkGetRequest();

        $sql = 'SELECT ui.*, rt.token AS token FROM `user_invite` ui
    LEFT JOIN `referral_token` rt ON ui.referral_token_id  = rt.id
WHERE ui.status = ? ;';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([InviteEnum::STATUS_PENDING]);

        $invites = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        $header = ['invite_email', 'sent_at', 'status', 'referral_url'];
        $data = [];

        $protocol = app('config')->get('mailTemplate.siteUrl.pley.protocol');
        $domain = app('config')->get('mailTemplate.siteUrl.pley.domain');

        foreach ($invites as $invite) {
            $data[] = [
                $invite['invite_email'],
                $invite['created_at'],
                InviteEnum::asString($invite['status']),
                sprintf('%s://%s/?referralToken=%s', $protocol, $domain, $invite['token'])
            ];
        }
        return new StreamedResponse(
            function () use ($data, $header) {
                // A resource pointer to the output stream for writing the CSV to
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $header);
                foreach ($data as $row) {
                    // Loop through the data and write each entry as a new row in the csv
                    fputcsv($handle, $row);
                }

                fclose($handle);
            },
            200,
            [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=members.csv'
            ]
        );
    }
}