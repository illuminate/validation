<?php namespace Illuminate\Validation;

use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$this->registerPresenceVerifier($app);

		$app['validator'] = $app->share(function($app)
		{
			$validator = new Factory($app['translator']);

			// The validation presence verifier is responsible for determing the existence
			// of values in a given data collection, typically a relational database or
			// other persistent data stores. And it is used to check for uniqueness.
			if (isset($app['validation.presence']))
			{
				$validator->setPresenceVerifier($app['validation.presence']);
			}

			return $validator;
		});
	}

	/**
	 * Register the database presence verifier.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerPresenceVerifier($app)
	{
		$app['validation.presence'] = $app->share(function($app)
		{
			return new DatabasePresenceVerifier($app['db']);
		});
	}

}