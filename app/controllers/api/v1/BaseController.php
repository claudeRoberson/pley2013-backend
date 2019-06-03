<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace api\v1;

/**
 * The <kbd>BaseController</kbd> is to be used instead of the one provided by Laravel because we
 * do not need to initialize the <kbd>setupLayout</kbd> method.
 * <p>At the same time it also provides a base foundation for all versioned controllers in case
 * we need to do something in particular for all of them.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package api.v1
 * @subpackage v1
 */
abstract class BaseController extends \api\shared\AbstractBaseController
{}