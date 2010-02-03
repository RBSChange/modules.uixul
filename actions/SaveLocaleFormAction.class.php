<?php
class uixul_SaveLocaleFormAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$provider = $this->getPersistentProvider();
		$path = $request->getParameter('path');
		$key = $request->getParameter('key');
		$fullKey = $path.'.'.$key;
		$langs = explode(',', $request->getParameter('langs'));
		$locale = $provider->getLocaleByKey($fullKey);
		
	    try
		{
			foreach ($langs as $lang)
			{
				$value = $request->getParameter('value_' . $lang);
				// If the value is empty, restore the original value.
				$originalValue = $locale[$lang]['originalcontent'];
				if ($value == '' || $value == $originalValue)
				{
					if ($locale[$lang]['useredited'])
					{
						$provider->clearTranslationKeyForLang($fullKey, $lang);
						if ($originalValue)
						{
							$provider->addTranslate($fullKey, $lang, $originalValue, $path, '0', '1', '0');
						}
					}
				}
				else if (!isset($locale[$lang]) || ($value != $locale[$lang]['content']))
				{
					$provider->addTranslate($fullKey, $lang, $value, $path, '0', '1', '1');
				}
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return $this->getErrorView();
		}
		
		return $this->getSuccessView();
	}
}