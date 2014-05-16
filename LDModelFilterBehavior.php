<?php
/**
 * LDModelFilterBehavior class file.
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 * @link https://lou-d.com
 * @copyright 2014 Louis A. DaPrato
 * @license The MIT License (MIT)
 * @since 1.0
 */

/**
 * LDModelFilterBehavior converts and filters arrays of data that are associated with a model. 
 * This behavior will convert/filter arrays of models, arrays, or a mixture of both.
 *
 * 
 * To use LDModelFilterBehavior attach this behavior to the model that your raw data array is associated with either inline 
 * using {@link CComponent::attachBehavior()} or {@link CComponent::attachBehaviors()} or statically as follows.
 * 
 * In associated model:
 * <pre>
 * public function behaviors()
 * {
 * 		return array(
 * 			'LDModelFilterBehavior' => array(
 * 				'class' => 'ext.LDModelFilterBehavior.LDModelFilterBehavior', // or wherever this class is located in your app
 * 				'callbacks' => array() // optionally specify this option if you want to use callbacks for custom comparisons for certain attributes in your data 
 * 			)
 * 		);
 * }
 * </pre>
 * 
 * When you want to filter an array of data set the attribute values of your model as you would normally when filtering data using {@link CActiveDataProvider}.
 * Then call the {@see LDModelFilterBehavior::filter()} method that this behavior has added to your model. Pass to that method your raw data and
 * the filtered form of that data will be returned to you. 
 * 
 * A simple example to convert an array of models to an array of arrays where each array is a data row with properties as keys and values as values
 * 
 * FooModel::model()->filter(FooModel::model()->findAll());
 * 
 * A slightly more complex example involving conversion and filtering
 * (note that if FooModel has non-database attributes set the findAll() method will not filter the data by those values, but the filter method provided by this behavior will)
 * 
 *  $fooModel = new FooModel();
 * 	if(isset($_POST['FooModel']))
 * 	{
 * 		$fooModel->setAttributes($_POST['FooModel']);
 * 	}
 * 	$filteredRawFooData = $fooModel->filter($fooModel->findAll());
 * 
 * A complete example using a {@link CArrayDataProvider} with a {@link CGridView} and a mystery data source
 * 
 * Controller action:
 * <pre>
 * public function actionFoo()
 * {
 * 		$fooModel = new FooModel();
 * 		if(isset($_POST['FooModel']))
 * 		{
 * 			$fooModel->setAttributes($_POST['FooModel']);
 * 		}
 * 		$rawFooData = $this->generateRawFooData();
 * 		$filteredRawFooData = $fooModel->filter($rawFooData);
 * 		$dataProvider = new CArrayDataProvider($filteredRawFooData);
 * 		$this->render('foo', array('model' => $fooModel, 'dataProvider' => $dataProvider));
 * }
 * </pre>
 * 
 * View 'foo':
 * <pre>
 * $this->widget('zii.widgets.grid.CGridView',
 *		array(
 *			'filter' => $model,
 *			'dataProvider' => $dataProvider,
 *			'columns' => array(
 *				...
 *			)
 *		)
 *	);
 * </pre>
 * 
 * By default this behavior will only filter, or unset, a data row if BOTH of the following conditions are true. 
 * 	1. The function empty() returns true on the associated model attribute's value.
 * 	2. There is a partial match between the string value of the associated model attribute's value and the raw data value.
 * 
 * If you need to specify different comparisons for your data you may optionally specify custom callbacks for comparing attributes 
 * by setting the {@see LDModelFilterBehavior::$callbacks} property. This property can be set in the behavior's configuration or 
 * can be passed on the fly to the {@see LDModelFilterBehavior::filter()} method.
 * If a callback is set for a particular attribute the return value of your defined callback will be used to determine whether the 
 * row should be filtered or not.
 * Your callback must strictly return false for the data row to be filtered. Any oher value will not cause the data row to be filtered.
 * Your callback should accept 3 parameters as follows:
 * 	1. The name of the attribute
 * 	2. The model's attribute's value
 * 	3. The associated raw data row's attribute's value
 * 
 * By default the {@see LDModelFilterBehavior::filter()} method will only compare safe attribute values. This can be disabled
 * by passing false as the argument to the safeOnly parameter of the {@see LDModelFilterBehavior::filter()} method.
 * 
 * Each of the following properties can be overriden at runtime by passing a non-null value to their respective arguments of the filter() method
 * 
 * @property boolean $ignoreUndefinedAttributes If true and a value cannot be normalized before comparison it will be ignored. If false filtering will attempt to continue normally, any errors will propogate as expected. Defaults to true.
 * @property boolean $normalizeData If true data will be normalized to a raw array, otherwise data will only be filtered, not altered. Defaults to true.
 * @property mixed $attributeNames If true only the data will be filtered only by safe attribute names. If an array the data will be filtered by the attribute names specified by the array.
 * @property array $callbacks a list of comparison callbacks in the form array('attribute name' => 'callable')
 * 
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 */
class LDModelFilterBehavior extends CModelBehavior
{
	
	/**
	 * @var boolean If true and a value cannot be normalized before comparison it will be ignored. If false filtering will attempt to continue normally, any errors will propogate as expected. Defaults to true.
	 */
	public $ignoreUndefinedAttributes = true;
	
	/**
	 * If true all filtered data will be returned as a raw array with keys being the attributes/property names you are filtering by and 
	 * the values being their respective value. Otherwise the data will only be filtered, not unaltered. Defaults to true.
	 * 
	 * @var boolean If true data will be normalized to a raw array, otherwise data will only be filtered, not altered. Defaults to true.
	 */
	public $normalizeData = true;
	
	/**
	 * The attribute name to filter the data by. 
	 * If true only safe attribute names or if an array then the data will be filtered only by those attributes
	 * 
	 * @var mixed True meaning safe attribute names only or an array of attribute names to filter the data by
	 */
	public $attributeNames = true;
	
	/**
	 * Set this if you require custom comparisons to be done for certain attributes.
	 * The default comparison checks if the value is empty or if there is a partial match of the string value of the attribute and the data.
	 * If either is true the data row will NOT be filtered.
	 * 
	 * The comparison callback should explicitly return false if the data row should be filtered.
	 * Any other return value will NOT cause the row to be filtered.
	 * 
	 * @var array a list of comparison callbacks in the form array('attribute name' => 'callable')
	 */
	public $callbacks = array();

	/**
	 * Filters an array of data using the attribute values of the model that owns this behavior.
	 * Each index/row of the data array is expected to be in the format array('attribute name' => 'attribute value', ...)
	 * 
	 * @param array $data The data to be filtered
	 * @param mixed $attributeNames The attribute name(s) to compare at each data row. Defaults to true meaning safe attributes.
	 * @param array $callbacks A list of comparison callbacks in the form array('attribute name' => 'callable') {@see LDModelFilterBehavior::$callbacks}
	 * @param boolean $normalizeData {@see LDModelFilterBehavior::$normalizeData}
	 * @param boolean $ignoreUndefinedAttributes {@see LDModelFilterBehavior::$ignoreUndefinedAttributes}
	 * @return array The filtered data
	 */
	public function filter(array $data, $attributeNames = null, $callbacks = null, $normalizeData = null, $ignoreUndefinedAttributes = null)
	{
		// Load parameter defaults
		if($attributeNames === null)
		{
			$attributeNames = $this->attributeNames;
		}
		if($callbacks === null)
		{
			$callbacks = $this->callbacks;
		}
		if($normalizeData === null)
		{
			$normalizeData = $this->normalizeData;
		}
		if($ignoreUndefinedAttributes === null)
		{
			$ignoreUndefinedAttributes = $this->ignoreUndefinedAttributes;
		}
		
		$owner = $this->getOwner();
		// Load attribute values
		$attributes = array();
		foreach(($attributeNames === true ? $owner->getSafeAttributeNames() : ($attributeNames === null ? $owner->attributeNames() : (array)$attributeNames)) as $attributeName)
		{
			$attributes[$attributeName] = $owner->$attributeName;
		}

		foreach($data as $rowIndex => $row)	// for each data row
		{
			foreach($attributes as $name => &$value) // for each attribute value of the model we are filtering by
			{
				// normalize the row value
				if(is_array($row)) // if the row is an array
				{
					if(array_key_exists($name, $row) || !$ignoreUndefinedAttributes) // if the attribute name is a key or we are ignoring undefined attributes
					{
						$rowValue = $row[$name];
					}
					else // The attribute name is not defined and we can ignore, do so.
					{
						continue;
					}
				}
				else if(is_object($row)) // If the row is an object
				{
					try // try to get the property value of the attribute name
					{
						$rowValue = $row->$name;
					}
					catch(Exception $e) // if we failed to get the property value
					{
						if($ignoreUndefinedAttributes) // if we can ignore the exception, do so.
						{
							continue;
						}
						else // if we can't ignore the exception, throw it further up the stack.
						{
							throw $e;
						}
					}
				}
				else // if the value was not an array or object it must be a scalar.
				{
					$rowValue = $row;
				}
				
				if($normalizeData) // If we need to normalize the data source, do so.
				{
					if(is_array($data[$rowIndex]))
					{
						$data[$rowIndex][$name] = $rowValue;
					}
					else 
					{
						$data[$rowIndex] = array($name => $rowValue);
					}
				}

				if(isset($callbacks[$name])) // If a callback is set for comparisons done for the current atttribute, use it.
				{
					if(call_user_func($callbacks[$name], $name, $value, $row) !== false) // If the callback did not explicitly return false don't filter this row
					{
						continue;
					}
				}
				else if($value === null || $value === '') // If the filter model's attribute value is null or empty string don't filter this row
				{
					continue;
				}
				else if(is_string($rowValue)) // If the row value is a string
				{
					if(is_array($value)) // If the attribute value is an array
					{
						if(in_array($rowValue, $value)) // If the row value is in the attribute value array don't filter this row
						{
							continue;
						}
					}
					else if(stripos($rowValue, (string)$value) !== false) // If the row value is a partial match to the string value of the attribute don't filter this row
					{
						continue;
					}
				}
				else if(is_array($value)) // If the row value is not a string and the attribute value is an array
				{
					if(in_array($rowValue, $value)) // If the row value is in the attribute value array don't filter this row
					{
						continue;
					}
				} 
				else if($rowValue == $value) // If row value is not a string and attribute value is not an array and both values are equal don't filter this row.
				{
					continue;
				}

				unset($data[$rowIndex]); // Filter this row
				break; // Stop comparing attributes in this row as it has already been filtered
			}
		}
		
		return $data;
	}

}
?>
