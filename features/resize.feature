Feature: Resize image by url
  As I website visitor
  I want to download smaller version of image
  So that website is loaded faster

Scenario Outline: Resize by only width
  When I request "images/cache/<parameter>/<image>" on the server
  Then I see image with dimensions of <width> by <height> px
  Examples:
    | image               | parameter | width | height |
    | square.jpg          | 300x      | 300   | 300    |
    | landscape.jpg       | 300x      | 300   | 225    |
    | portrait.jpg        | 300x      | 225   | 300    |


  Scenario Outline: Resize by width and height
    When I request "images/cache/<parameter>/<image>" on the server
    Then I see image with dimensions of <width> by <height> px
    Examples:
      | image               | parameter | width | height |
      | square.jpg          | 300x300   | 300   | 300    |
      | square.jpg          | 300x200   | 200   | 200    |
      | landscape.jpg       | 300x300   | 300   | 225    |
      | landscape.jpg       | 400x300   | 400   | 300    |
      | portrait.jpg        | 300x300   | 225   | 300    |
      | portrait.jpg        | 300x400   | 300   | 400    |



