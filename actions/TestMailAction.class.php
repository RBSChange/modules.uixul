<?php
class uixul_TestMailAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		if ($request->hasParameter('post')
		&& (intval($request->getParameter('post')) == 1))
		{
			if ($request->hasParameter('mails'))
			{
				$error = null;
				$mails = array();
				$inputMails = preg_split('/[\s,;]+/', $request->getParameter('mails'));
				foreach ($inputMails as $inputMail)
				{
					$inputMail = trim($inputMail);
					if (preg_match('/^[a-z0-9][a-z0-9_.-]*@[a-z][a-z0-9.-]*\.[a-z]{2,3}$/i', $inputMail))
					{
						$mails[] = $inputMail;
					}
				}
				$mails = array_unique($mails);
				if (!empty($mails))
				{
					$execute = true;
				}
				else
				{
					$execute = false;
				}
			}
			else
			{
				$execute = false;
			}

			if ($request->hasParameter('subject')
			&& $request->getParameter('subject'))
			{
				$execute = true;
			}
			else
			{
				$execute = false;
			}

			if ($execute
			&& $request->hasParameter('fileContent')
			&& $request->getParameter('fileContent'))
			{
				$inputFile = $request->getParameter('fileContent');
				$execute = true;
			}
			else
			{
				$execute = false;
			}

			if ($execute)
			{
				$ms = MailService::getInstance();

				foreach ($mails as $mail)
				{
					$mgs = $ms->getNewMailMessage();

					$mgs->setSubject($request->getParameter('subject'))
						->setSender(Framework::getDefaultNoReplySender())
						->setReceiver($mail)
						->setEncoding('utf-8')
						->setHtmlAndTextBody($inputFile, f_util_HtmlUtils::htmlToText($inputFile));

					$ms->send($mgs);
				}
			}

			return change_View::NONE;
		}

		return change_View::SUCCESS;
	}
}