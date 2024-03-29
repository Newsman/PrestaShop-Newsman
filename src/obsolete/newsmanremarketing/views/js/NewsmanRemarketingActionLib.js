/**
 * Copyright 2019 Dazoot Software
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Lucian for Newsman
 * @copyright 2019 Dazoot Software
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

/* globals $, _nzm.run, jQuery */

//window.addEventListener('load', function () {

//jQuery(document).ready(function () {

let jLoadedNewsman = false;
let NewsmanAnalyticEnhancedECommerce = null;

function cJNewsman()
{
  
  if (window.jQuery) 
  {
        
    setTimeout(function(){    

        jLoadedNewsman = true;
          
        //default class name .product-container
        var currClass = ".product-container";
        //default class name .ajax-add-to-cart
        var currClassId = ".ajax-add-to-cart";
        //default class name .product-title
        var currClassName = ".product-title";
        //default class name .title-page
        var currClassCategory = ".title-page";
        //default class name .content_price
        var currClassPrice = ".content_price";

        //category view / add to cart
        var _items = jQuery(currClass);

        if (_items.length > 0) {
          //category view
          for (var x = 0; x <= _items.length; x++) {
            if (newsmanVersion.indexOf("1.7") >= 0) {
              var currentProd = jQuery(currClass + ":eq(" + x + ")");
 
              var id = currentProd.find(currClassId);
              id = id.attr("data-id-product");

              if (id == "" || id == undefined) continue;

              var name = currentProd.find(currClassName).text();
              name = $.trim(name);
              var category = $(currClassCategory).html();
              var price = currentProd.find(currClassPrice + " span:first").text();
              price = $.trim(price);

              _nzm.run("ec:addImpression", {
                id: id,
                name: name,
                category: category,
                list: "Category List",
                position: x,
              });
            } else if (newsmanVersion.indexOf("1.6") >= 0) {
              var currentProd = jQuery(currClass + ":eq(" + x + ")");

              currClassId = ".ajax_add_to_cart_button";

              var id = currentProd.find(currClassId);
              id = id.attr("data-id-product");

              if (id == "" || id == undefined) continue;

              currClassName = ".product-name";

              var name = currentProd.find(currClassName).text();
              name = $.trim(name);

              currClassCategory = ".cat-name";

              var category = $(currClassCategory).html();
              var price = currentProd.find(currClassPrice + " span:first").text();
              price = $.trim(price);

              if (category == undefined) category = "All";

              _nzm.run("ec:addImpression", {
                id: id,
                name: name,
                category: category,
                list: "Category List",
                position: x,
              });
            }
          }

          _nzm.run("send", "pageview");

          /*
          //add to cart
          for (var x = 0; x <= _items.length; x++) {
            if (newsmanVersion.indexOf("1.7") >= 0) {
              $(".product-container:eq(" + x + ") .ajax-add-to-cart").on(
                "click",
                function () {
                  var _c = $(this).closest(".product-container");

                  var id = $(this).attr("data-id-product");
                  var name = _c.find(".product-title").text();
                  name = $.trim(name);
                  var category = "";
                  var price = _c
                    .find(".product-price-and-shipping span.price")
                    .text();
                  price = $.trim(price);

                  _nzm.run("ec:addProduct", {
                    id: id,
                    name: name,
                    category: category,
                    price: price,
                    quantity: "1",
                  });
                  _nzm.run("ec:setAction", "add");
                  _nzm.run("send", "event", "UX", "click", "add to cart");
                }
              );
            } else if (newsmanVersion.indexOf("1.6") >= 0) {
              $(".product-container:eq(" + x + ") .ajax_add_to_cart_button").on(
                "click",
                function () {
                  var _c = $(this).closest(".product-container");

                  var id = $(this).attr("data-id-product");
                  var name = _c.find(".product-name").text();
                  name = $.trim(name);
                  var category = "";
                  var price = _c.find(".content_price span:first").text();
                  price = price.trim();
                  price = price.split(' ').join('');
                  price = price.split(',').join('.');
                  price = price.split('lei').join('');

                  if (category == undefined) category = "All";

                  _nzm.run("ec:addProduct", {
                    id: id,
                    name: name,
                    category: category,
                    price: price,
                    quantity: "1",
                  });
                  _nzm.run("ec:setAction", "add");
                  _nzm.run("send", "event", "UX", "click", "add to cart");

                  setTimeout(function () {
                    removeFromCartWidget16();
                  }, 2000);
                }
              );
            }
          }
          */

        }

        /*
        function addToCart16()
        {
          //add to cart - prestashop 1.6.x
          $("#add_to_cart").click(function () {
            var id = $("#product_page_product_id").val();
            var name = $("h1:first").text();
            name = $.trim(name);
            var category = "";
            var price = $("#our_price_display").text();
            price = $.trim(price);
            var _qty = $("#quantity_wanted").val();

            if (category == undefined) category = "All";

            _nzm.run("ec:addProduct", {
              id: id,
              name: name,
              category: category,
              price: price,
              quantity: _qty,
            });
            _nzm.run("ec:setAction", "add");
            _nzm.run("send", "event", "UX", "click", "add to cart");

            setTimeout(function () {
              removeFromCartWidget16();
            }, 2000);
          });
        }

        function addToCart17() {
          //add to cart - prestashop 1.7.x
          $("body").on("click", ".add-to-cart", function (e) {
            var attr = $(".product-variants input[type=radio]:checked");
            var attr = attr.closest(".input-container").find(".radio-label").html();

            var id = $("#product_page_product_id").val();
            var name = $("h1:first").text();
            name = $.trim(name);
            var category = "";
            var price = $(".current-price span").attr("content");
            var _qty = $("#quantity_wanted").val();

            _nzm.run("ec:addProduct", {
              id: id,
              name: name,
              category: category,
              price: price,
              quantity: _qty,
            });
            _nzm.run("ec:setAction", "add");
            _nzm.run("send", "event", "UX", "click", "add to cart");

            setTimeout(function () {
              removeFromCartWidget17();
            }, 2000);
          });
        }

        function removeFromCartWidget17() {
          //delete from cart widget prestashop 1.7
          $(".ajax_remove_button").each(function () {
            jQuery(this).bind("click", { elem: jQuery(this) }, function (ev) {
              var _c = $(this).closest(".small_cart_info");

              var id = _c.find(".cart_quantity");
              id = id.attr("data-product-id");
              var qty = _c.find(".cart_quantity").val();

              _nzm.run("ec:addProduct", {
                id: id,
                quantity: qty,
              });

              _nzm.run("ec:setAction", "remove");
              _nzm.run("send", "event", "UX", "click", "remove from cart");
            });
          });
        }

        function clickedVariants() {
          $(".product-variants input[type=radio]").on("change", function () {});
        }

        //delete from cart
        $(".cart_quantity_delete").each(function () {
          jQuery(this).bind("click", { elem: jQuery(this) }, function (ev) {
            var _c = $(this).closest(".cart_item");

            var id = ev.data.elem.attr("id");
            id = id.substr(0, id.indexOf("_"));
            var qty = _c.find(".cart_quantity_input").val();

            _nzm.run("ec:addProduct", {
              id: id,
              quantity: qty,
            });

            _nzm.run("ec:setAction", "remove");
            _nzm.run("send", "event", "UX", "click", "remove from cart");

          });
        });

        function removeFromCartWidget16()
        {

          //delete from cart widget
          $(".ajax_cart_block_remove_link").each(function () {

            jQuery(this).off('click');

            jQuery(this).bind("click", { elem: jQuery(this) }, function (ev) {
              var _c = $(this).closest(".first_item");

              var id = _c.attr("data-id");
              id = id.replace("cart_block_product_", "");
              id = id.substr(0, id.indexOf("_"));

              var qty = _c.find(".quantity").html();

              _nzm.run("ec:addProduct", {
                id: id,
                quantity: qty,
              });

              _nzm.run("ec:setAction", "remove");
              _nzm.run("send", "event", "UX", "click", "remove from cart");
            });
          });

       }
       */

       setTimeout(function () {
        /*clickedVariants();
        addToCart17();
        removeFromCartWidget17();
        addToCart16();
        removeFromCartWidget16();

        //delete from cart prestashop 1.7
        $(".remove-from-cart").each(function () {
          jQuery(this).bind("click", { elem: jQuery(this) }, function (ev) {
            var _c = $(this).closest(".cart_item");

            var id = ev.data.elem.attr("data-id-product");
            var qty = _c.find("[name=product-quantity-spin]").val();

            _nzm.run("ec:addProduct", {
              id: id,
              quantity: qty,
            });

            _nzm.run("ec:setAction", "remove");
            _nzm.run("send", "event", "UX", "click", "remove from cart");
          });
        });*/

      }, 1500);    

      NewsmanAnalyticEnhancedECommerce = {
        add: function (Product, Order, Impression) {
          var Products = {};
          var Orders = {};

          var ProductFieldObject = [
            "id",
            "name",
            "category",
            "brand",
            "variant",
            "price",
            "quantity",
            "coupon",
            "list",
            "position",
            "dimension1",
          ];
          var OrderFieldObject = [
            "id",
            "affiliation",
            "revenue",
            "tax",
            "shipping",
            "coupon",
            "list",
            "step",
            "option",
          ];

          if (Product != null) {
            if (Impression && Product.quantity !== undefined) {
              delete Product.quantity;
            }

            for (var productKey in Product) {
              for (var i = 0; i < ProductFieldObject.length; i++) {
                if (productKey.toLowerCase() == ProductFieldObject[i]) {
                  if (Product[productKey] != null) {
                    if (
                      productKey == "name" ||
                      productKey == "category" ||
                      productKey == "brand"
                    ) {
                      var str = Product[productKey];
                      str = str.toString().replace('"', "");
                      Product[productKey] = str.toString().replace('"', "");
                    }

                    Products[productKey.toLowerCase()] = Product[productKey];
                  }
                }
              }
            }
          }

          if (Order != null) {
            for (var orderKey in Order) {
              for (var j = 0; j < OrderFieldObject.length; j++) {
                if (orderKey.toLowerCase() == OrderFieldObject[j]) {
                  Orders[orderKey.toLowerCase()] = Order[orderKey];
                }
              }
            }
          }

          if (Impression) {
		if (newsmanVersion.indexOf("1.7") >= 0) {
	           _nzm.run("ec:addImpression", Products);
		}
          } else {
            _nzm.run("ec:addProduct", Products);
          }
        },
        addProductDetailView: function (Product) {
          this.add(Product);
          _nzm.run("ec:setAction", "detail");
          _nzm.run("send", "pageview");
        },
        addToCart: function (Product) {},
        removeFromCart: function (Product) {},
        addProductImpression: function (Product) {},
        refundByOrderId: function (Order) {},
        refundByProduct: function (Order) {},

        addProductClick: function (Product) {},
        addProductClickByHttpReferal: function (Product) {},
        addTransaction: function (Order) {
          _nzm.identify({
            email: Order.email,
            first_name: Order.firstname,
            last_name: Order.lastname,
          });

          _nzm.run("ec:setAction", "purchase", {
            id: Order.id,
            affiliation: Order.affiliation,
            revenue: Order.revenue,
            tax: Order.tax,
            shipping: Order.shipping,
          });

          _nzm.run("send", "pageview");
        },
        addCheckout: function (Step) {},
      };

  }, 1000);

}
else{
  setTimeout(function(){
          cJNewsman();
      }, 1000);
}

}

cJNewsman();

var isProd = false;
let lastCart = sessionStorage.getItem('lastCart');
if (lastCart === null)
    lastCart = {};
let lastCartFlag = false;
let bufferedClick = false;
let firstLoad = true;
NewsmanAutoEvents();
setInterval(NewsmanAutoEvents, 5000);
BufferClick();

function NewsmanAutoEvents() {

      let postObj = { 
        post: true
    }

    var ajaxurl = '/newsmanfetch.php?newsman=getCart.json';

    let post = JSON.stringify(postObj)
  
    let xhr = new XMLHttpRequest()
    
    if (bufferedClick || firstLoad) {
             
    xhr.open('GET', ajaxurl, true);
    xhr.onload = function() {
      
      if(xhr.status == 200 || xhr.status == 201)
      {
          var response = JSON.parse(xhr.responseText);        

          lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
          if (lastCart === null)
              lastCart = {};
          //check cache
          if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 &&
              response != null && response != undefined) {
              if (JSON.stringify(lastCart) === JSON.stringify(response)) {
                  if (!isProd)
                      console.log('newsman remarketing: cache loaded, cart is unchanged');
                  lastCartFlag = true;
              } else {
                  lastCartFlag = false;
              }
          }
          if (response.length > 0 && lastCartFlag == false) {
              _nzm.run('ec:setAction', 'clear_cart');
              _nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
              for (var item in response) {
                  _nzm.run('ec:addProduct',
                      response[item]
                  );
              }

              _nzm.run('ec:setAction', 'add');
              _nzm.run('send', 'event', 'UX', 'click', 'add to cart');
              sessionStorage.setItem('lastCart', JSON.stringify(response));
              if (!isProd)
                  console.log('newsman remarketing: cart sent');
          } else {
              if (!isProd)
                  console.log('newsman remarketing: request not sent');
          }
          firstLoad = false;
          bufferedClick = false;    

        }
     }    
     
     xhr.send(null);

  }
}

function BufferClick() {
  window.onclick = function(e) {
      const origin = ['a', 'input', 'span', 'i', 'button'];

      var click = e.target.localName;
      if (!isProd)
          console.log('newsman remarketing element clicked: ' + click);
      for (const element of origin) {
          if (click == element)
              bufferedClick = true;
      }
  }
}

//});
