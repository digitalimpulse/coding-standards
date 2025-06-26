# DemandDrive PHPCS Standards

Custom PHPCS sniffs for enforcing additional coding standards on DemandDrive PHP projects.

## Installation

Install via Composer:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/digitalimpulse/coding-standards"
    }
  ],
  "require": {
    "digitalimpulse/phpcs-coding-standards": "v0.1"
  }
}
```

## Configuration

Create a `phpcs.xml` or `phpcs.xml.dist` file in your project root:

```xml
<?xml version="1.0"?>
<ruleset name="My Project Standards">
	<!-- Include DemandDrive standards -->
	<rule ref="DemandDrive"/>

	<!-- Project-specific configuration -->
	<file>.</file>
	<exclude-pattern>*/vendor/*</exclude-pattern>
	<exclude-pattern>*/node_modules/*</exclude-pattern>

	<!-- Override specific rules if needed -->
	<rule ref="WordPress.WP.I18n">
		<properties>
			<property name="text_domain" type="array">
				<element value="my-textdomain"/>
			</property>
		</properties>
	</rule>
</ruleset>
```

### Creating New Sniffs

1. Create a new sniff class in `src/Standards/DemandDrive/Sniffs/Category/SniffNameSniff.php`
2. Implement the `Sniff` interface
3. Add the sniff rule to `src/Standards/DemandDrive/ruleset.xml`
4. Create tests in `tests/Category/SniffNameSniffTest.php`
