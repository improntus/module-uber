<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

abstract class AbstractIndex extends Action
{
    /**
     * @var string
     */
    private $pageTitle;

    /**
     * @var string
     */
    protected $activeMenuItem;

    /**
     * @param Context $context
     * @param string $activeMenuItem
     * @param string $pageTitle
     */
    public function __construct(Context $context, string $activeMenuItem = '', string $pageTitle = '')
    {
        parent::__construct($context);
        $this->activeMenuItem = $activeMenuItem;
        $this->pageTitle = $pageTitle;
    }

    /**
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        if ($this->activeMenuItem) {
            $resultPage->setActiveMenu($this->activeMenuItem);
        }
        $resultPage->getConfig()->getTitle()->prepend($this->pageTitle);
        return $resultPage;
    }
}
