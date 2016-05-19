<?php

namespace fitch\fields;

class ManyRelation extends \fitch\fields\Relation {
	public function isMany() {
		return true;
	}
}

?>