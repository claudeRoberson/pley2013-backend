<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace operations\v1\Marketing\Banner;

/**
 * The <kbd>RevealBannerController</kbd> responsible on making CRUD operations on a banner entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RevealBannerController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Repository\Banner\RevealBannerRepository */
    protected $_revealBannerRepository;

    public function __construct(
        \Pley\Repository\Banner\RevealBannerRepository $revealBannerRepository
    )
    {
        parent::__construct();
        $this->_revealBannerRepository = $revealBannerRepository;
    }

    // POST /marketing/banner
    public function create()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $revealBanner = new \Pley\Entity\Banner\RevealBanner();
        $revealBanner->fill(\Input::json('banner'));

        $this->_revealBannerRepository->save($revealBanner);
        return \Response::json($revealBanner, 201);
    }

    // GET /marketing/banner
    public function getAll()
    {
        $response = [
            'banners' => []
        ];
        \RequestHelper::checkGetRequest();
        $revealBanners = $this->_revealBannerRepository->all();
        foreach ($revealBanners as $banner){
            $response['banners'][] = $banner->toArray();
        }
        return \Response::json($response);
    }

    // GET /marketing/banner/{id}
    public function get($id)
    {
        \RequestHelper::checkGetRequest();
        $revealBanner = $this->_revealBannerRepository->find($id);
        \ValidationHelper::entityExist($revealBanner, \Pley\Entity\Banner\RevealBanner::class);
        $response = $revealBanner->toArray();

        return \Response::json($response);
    }

    // PUT /marketing/banner/{id}
    public function update($id)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();

        $revealBanner = $this->_revealBannerRepository->find($id);
        \ValidationHelper::entityExist($revealBanner, \Pley\Entity\Banner\RevealBanner::class);

        $revealBanner->fill(\Input::json('banner'));
        $this->_revealBannerRepository->save($revealBanner);

        return \Response::json($revealBanner);
    }
}