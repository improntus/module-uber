<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Improntus\Uber\Api\ExecutorInterface;
use Magento\Framework\Controller\ResultFactory;

abstract class AbstractDelete extends Action
{
    /**
     * @var string
     */
    protected $paramName;

    /**
     * @var ExecutorInterface
     */
    protected $executor;

    /**
     * @var string
     */
    protected $successMessage;

    /**
     * @var string
     */
    protected $missingEntityErrorMessage;

    /**
     * @var string
     */
    protected $generalErrorMessage;

    /**
     * @param Context $context
     * @param ExecutorInterface $executor
     * @param string $paramName
     * @param string $successMessage
     * @param string $missingEntityErrorMessage
     * @param string $generalErrorMessage
     */
    public function __construct(
        Context $context,
        ExecutorInterface $executor,
        string $paramName,
        string $successMessage,
        string $missingEntityErrorMessage,
        string $generalErrorMessage
    ) {
        parent::__construct($context);
        $this->paramName = $paramName;
        $this->executor = $executor;
        $this->successMessage = $successMessage;
        $this->missingEntityErrorMessage = $missingEntityErrorMessage;
        $this->generalErrorMessage = $generalErrorMessage;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $id = $this->getRequest()->getParam($this->paramName);
        if ($id) {
            try {
                $this->executor->execute($id);
                $this->messageManager->addSuccessMessage($this->successMessage);
                $resultRedirect->setPath('*/*/');
                return $resultRedirect;
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage($this->missingEntityErrorMessage);
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', [$this->paramName => $id]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($this->generalErrorMessage);
                return $resultRedirect->setPath('*/*/edit', [$this->paramName => $id]);
            }
        }
        $this->messageManager->addErrorMessage($this->missingEntityErrorMessage);
        $resultRedirect->setPath('*/*/');
        return $resultRedirect;
    }
}
