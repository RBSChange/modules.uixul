<?php
// +---------------------------------------------------------------------------+
// | This file is part of the WebEdit4 package.                                |
// | Copyright (c) 2005 RBS.                                                   |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class uixul_TestWControllerAction extends Action
{

	public function execute()
	{
		$context = $this->getContext();
		$request = $context->getRequest();

		$parentId      = $request->getParameter(K::PARENT_ID_ACCESSOR);
		$childrenOrder = $request->getParameter(K::CHILDREN_ORDER_ACCESSOR);
		/*
		if ( empty($childrenOrder) || empty($parentId) ) {
			$errorMessage = f_Locale::translate("&modules.generic.backoffice.OrderChildrenInvalidParametersErrorMessage;");

			$request->setAttribute("message", $errorMessage);
			return View::ERROR;
		}

		$errors = array();

		foreach ($childrenOrder as $childId => $order)
		{
			try {
				// The following line may throw a ClassException("instance_not_found")
				$relation = RelationComponent::getInstance($parentId, RelationComponent::IS_PARENT_OF, $childId);
				$relation->setSortOrder($order);
				$relation->save();
			} catch (ClassException $e) {
				$errors[] = $e->getLocaleMessage();
			}
		}

		if ( ! empty($errors) ) {
			$request->setAttribute("message", join(K::CRLF, $errors));
			return View::ERROR;
		}
		*/

		if ($request->hasParameter('time')) {
			$time = max(0, $request->getParameter('time'));
		} else {
			$time = 1;
		}
		sleep($time);

		Framework::log("[Action] val = ".$request->getParameter('val'), Logger::DEBUG);

		return View::SUCCESS;
	}

	public function getRequestMethods()
	{
		return Request::GET;
	}

/*
	public function isSecure()
	{
		return true;
	}
*/

}