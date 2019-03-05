# Image Resize Async Server
 
Asynchronious image resize server 


## Installation
```bash
composer require ecomdev/image-resize-server
```

## Documentation
See [tests](src/tests)

## Usage

```php
EcomDev\ImageResizeServer\ReactApplicationBuilder::create(8080)
    ->withBaseUrl('/path/to/cache/dir/via/url')
    ->withUrlPattern(':width:x:height:/:image:')
    ->withSavePath('path/to/save/images/cache'))
    ->withSourcePath('path/to/image/source/)
    ->build()
    ->run();
```

## License
This project is licensed under the END USER LICENSE License - see the [LICENSE](LICENSE) file for details
