LDModelFilterBehavior adds the ability for a CModel to convert and filter arrays of related data.
-------------------------------------------------------------------------------------------------
The CModel this behavior is attached to will be capable of converting and filtering arrays of models, arrays, or a mixture of both via a method called filter().

Requirements
------------

Yii 1.0 or above

Usage
-----

To use LDModelFilterBehavior first attach this behavior to the model that your data array is associated with either inline 
using CComponent::attachBehavior() or CComponent::attachBehaviors() or statically as follows.

In associated model:
```php
public function behaviors()
{
		return array(
			'LDModelFilterBehavior' => array(
				'class' => 'ext.LDModelFilterBehavior.LDModelFilterBehavior', // or wherever this class is located in your app
				'callbacks' => array() // optionally specify this option if you want to use callbacks for custom comparisons for certain attributes in your data 
			)
		);
}
```

When you want to filter an array of data set the attribute values of your model as you would normally when filtering data using CActiveDataProvider.
Then call the LDModelFilterBehavior::filter() method that this behavior has added to your model. Pass to that method your raw data and
the filtered form of that data will be returned to you. 

###A simple example to convert an array of models to an array of arrays where each array is a data row with properties as keys and values as values

FooModel::model()->filter(FooModel::model()->findAll());

###A slightly more complex example involving conversion and filtering
(note that if FooModel has non-database attributes set the findAll() method will not filter the data by those values, but the filter method will)

 $fooModel = new FooModel();
	if(isset($_POST['FooModel']))
	{
		$fooModel->setAttributes($_POST['FooModel']);
	}
	$filteredRawFooData = $fooModel->filter($fooModel->findAll());

###A complete example using a CArrayDataProvider with a CGridView and a mystery data source

In controller action called 'foo':
```php
public function actionFoo()
{
		$fooModel = new FooModel();
		if(isset($_POST['FooModel']))
		{
			$fooModel->setAttributes($_POST['FooModel']);
		}
		$rawFooData = $this->generateRawFooData();
		$filteredRawFooData = $fooModel->filter($rawFooData);
		$dataProvider = new CArrayDataProvider($filteredRawFooData);
		$this->render('foo', array('model' => $fooModel, 'dataProvider' => $dataProvider));
}
```

In the view called 'foo':
```php
$this->widget('zii.widgets.grid.CGridView',
		array(
			'filter' => $model,
			'dataProvider' => $dataProvider,
			'columns' => array(
				...
			)
		)
	);
```

By default this behavior will only filter, or unset, a data row if BOTH of the following conditions are true. 
	1. The function empty() returns true on the associated model attribute's value.
	2. There is a partial match between the string value of the associated model attribute's value and the raw data value.

If you need to specify different comparisons for your data you may optionally specify custom callbacks for comparing attributes 
by setting the LDModelFilterBehavior::$callbacks property. This property can be set in the behavior's configuration or 
can be passed on the fly to the LDModelFilterBehavior::filter() method.
If a callback is set for a particular attribute the return value of your defined callback will be used to determine whether the 
row should be filtered or not.
Your callback must strictly return false for the data row to be filtered. Any oher value will not cause the data row to be filtered.
Your callback should accept 3 parameters as follows:
	1. The name of the attribute
	2. The model's attribute's value
	3. The associated raw data row's attribute's value

By default the LDModelFilterBehavior::filter() method will only compare safe attribute values. This can be disabled
by passing false as the argument to the safeOnly parameter of the LDModelFilterBehavior::filter() method.
