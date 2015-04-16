<?php

/**
 * Extends {@link GridFieldDetailForm_ItemRequest}
 */
class CommentsGridFieldItemRequest extends DataExtension {

	private static $allowed_actions = array (
        'markSpam',
        'markApproved',
        'markUnapproved',
	);

	/**
	 * @param Form $form
	 */
	public function updateItemEditForm($form) {
		// Skip non-comment or non-existant records
		$record = $this->owner->record;
		if(!($record instanceof Comment) || !$record->exists()) return;

		// Remove approve / spam checkboxes
		$fields = $form->Fields();
		$moderated = $fields->dataFieldByName('Moderated');
		$isSpam = $fields->dataFieldByName('IsSpam');
		$fields->replaceField('Moderated', $moderated->performReadonlyTransformation());
		$fields->replaceField('IsSpam', $isSpam->performReadonlyTransformation());

		// Add custom actions
		$form->setActions($record->getCMSActions());
	}


	/**
	 * Saves the form and forwards to a blank form to continue creating
	 *
	 * @param array The form data
	 * @param Form The form object
	 */
	public function markSpam($data, $form) {
		if(!$this->owner->record->canEdit()) {
			return $controller->httpError(403);
		}
		$this->owner->record->markSpam();
		return $this->saveAndRedirect($data, $form);
	}


	/**
	 * Saves the form and goes back to list view
     *
	 *
	 * @param array The form data
	 * @param Form The form object
	 */
	public function markApproved($data, $form) {
		if(!$this->owner->record->canEdit()) {
			return $controller->httpError(403);
		}
		$this->owner->record->markApproved();
		return $this->saveAndRedirect($data, $form);
	}


    /**
     * Publishes the record and goes to make a new record
     * @param  array $data The form data
     * @param  Form $form The Form object
     * @return SS_HTTPResponse
     */
	public function markUnapproved($data, $form) {
		if(!$this->owner->record->canEdit()) {
			return $controller->httpError(403);
		}
		$this->owner->record->markUnapproved();
		return $this->saveAndRedirect($data, $form);
	}



	/**
	 * Traverse the nested RequestHandlers until we reach something that's not GridFieldDetailForm_ItemRequest.
	 * This allows us to access the Controller responsible for invoking the top-level GridField.
	 * This should be equivalent to getting the controller off the top of the controller stack via Controller::curr(),
	 * but allows us to avoid accessing the global state.
	 *
	 * GridFieldDetailForm_ItemRequests are RequestHandlers, and as such they are not part of the controller stack.
	 *
	 * @return Controller
	 */
	protected function getToplevelController() {
		$c = $this->popupController;
		while($c && $c instanceof GridFieldDetailForm_ItemRequest) {
			$c = $c->getController();
		}
		return $c;
	}

	protected function getBackLink(){
		// TODO Coupling with CMS
		$backlink = '';
		$toplevelController = $this->getToplevelController();
		if($toplevelController && $toplevelController instanceof LeftAndMain) {
			if($toplevelController->hasMethod('Backlink')) {
				$backlink = $toplevelController->Backlink();
			} elseif($this->popupController->hasMethod('Breadcrumbs')) {
				$parents = $this->popupController->Breadcrumbs(false)->items;
				$backlink = array_pop($parents)->Link;
			}
		}
		if(!$backlink) $backlink = $toplevelController->Link();

		return $backlink;
	}

	protected function saveAndRedirect($data, $form) {
		$new_record = $this->owner->record->ID == 0;
		$controller = $this->getToplevelController();
		$list = $this->owner->getGridField()->getList();

		if($list instanceof ManyManyList) {
			// Data is escaped in ManyManyList->add()
			$extraData = (isset($data['ManyMany'])) ? $data['ManyMany'] : null;
		} else {
			$extraData = null;
		}

		if(!$this->owner->record->canEdit()) {
			return $controller->httpError(403);
		}

		if (isset($data['ClassName']) && $data['ClassName'] != $this->owner->record->ClassName) {
			$newClassName = $data['ClassName'];
			// The records originally saved attribute was overwritten by $form->saveInto($record) before.
			// This is necessary for newClassInstance() to work as expected, and trigger change detection
			// on the ClassName attribute
			$this->owner->record->setClassName($this->owner->record->ClassName);
			// Replace $record with a new instance
			$this->owner->record = $this->owner->record->newClassInstance($newClassName);
		}

		try {
			$form->saveInto($this->owner->record);
			$this->owner->record->write();
			$list->add($this->owner->record, $extraData);
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad', false);
			$responseNegotiator = new PjaxResponseNegotiator(array(
				'CurrentForm' => function() use(&$form) {
					return $form->forTemplate();
				},
				'default' => function() use(&$controller) {
					return $controller->redirectBack();
				}
			));
			if($controller->getRequest()->isAjax()){
				$controller->getRequest()->addHeader('X-Pjax', 'CurrentForm');
			}
			return $responseNegotiator->respond($controller->getRequest());
		}

		// TODO Save this item into the given relationship

		$link = '<a href="' . $this->Link('edit') . '">"'
			. htmlspecialchars($this->owner->record->Title, ENT_QUOTES)
			. '"</a>';
		$message = _t(
			'GridFieldDetailForm.Saved',
			'Saved {name} {link}',
			array(
				'name' => $this->owner->record->i18n_singular_name(),
				'link' => $link
			)
		);

		$form->sessionMessage($message, 'good', false);

		if($new_record) {
			return $controller->redirect($this->Link());
		} elseif($this->owner->getGridField()->getList()->byId($this->owner->record->ID)) {
			// Return new view, as we can't do a "virtual redirect" via the CMS Ajax
			// to the same URL (it assumes that its content is already current, and doesn't reload)
			return $this->owner->edit($controller->getRequest());
		} else {
			$link = $this->owner->getGridField()->Link();
			return $controller->redirect($link);
		}
	}

}
