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

          }   

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

		//Newsman remarketing auto events REPLACEABLE

		var ajaxurl = 'https://' + document.location.hostname + '/napi?newsman=getCart.json';

		//Newsman remarketing auto events REPLACEABLE

		//Newsman remarketing auto events

		var isProd = true;
		let lastCart = sessionStorage.getItem('lastCart');
		if (lastCart === null)
			lastCart = {};
		var lastCartFlag = false;
		var firstLoad = true;
		var bufferedXHR = false;
		var unlockClearCart = true;
		var isError = false;
		let secondsAllow = 5;
		let msRunAutoEvents = 5000;
		let msClick = new Date();
		var documentComparer = document.location.hostname;
		var documentUrl = document.URL;
		var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);
		let startTime, endTime;
		function startTimePassed() {
			startTime = new Date();
		}
		;startTimePassed();
		function endTimePassed() {
			var flag = false;
			endTime = new Date();
			var timeDiff = endTime - startTime;
			timeDiff /= 1000;
			var seconds = Math.round(timeDiff);
			if (firstLoad)
				flag = true;
			if (seconds >= secondsAllow)
				flag = true;
			return flag;
		}
		if (sameOrigin) {
			NewsmanAutoEvents();
			setInterval(NewsmanAutoEvents, msRunAutoEvents);
			detectClicks();
			detectXHR();
		}
		function timestampGenerator(min, max) {
			min = Math.ceil(min);
			max = Math.floor(max);
			return Math.floor(Math.random() * (max - min + 1)) + min;
		}
		function NewsmanAutoEvents() {
			if (!endTimePassed()) {
				if (!isProd)
					console.log('newsman remarketing: execution stopped at the beginning, ' + secondsAllow + ' seconds didn\"t pass between requests');
				return;
			}
			if (isError && isProd == true) {
				console.log('newsman remarketing: an error occurred, set isProd = false in console, script execution stopped;');
				return;
			}
			let xhr = new XMLHttpRequest()
			if (bufferedXHR || firstLoad) {
				var paramChar = '?t=';
				if (ajaxurl.indexOf('?') >= 0)
					paramChar = '&t=';
				var timestamp = paramChar + Date.now() + timestampGenerator(999, 999999999);
				try {
					xhr.open('GET', ajaxurl + timestamp, true);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: malformed XHR url');
					isError = true;
				}
				startTimePassed();
				xhr.onload = function() {
					if (xhr.status == 200 || xhr.status == 201) {
						try {
							var response = JSON.parse(xhr.responseText);
						} catch (error) {
							if (!isProd)
								console.log('newsman remarketing: error occured json parsing response');
							isError = true;
							return;
						}
						//check for engine name
						//if shopify
						if (_nzmPluginInfo.indexOf('shopify') !== -1) {
							if (!isProd)
								console.log('newsman remarketing: shopify detected, products will be pushed with custom props');
							var products = [];
							if (response.item_count > 0) {
								response.items.forEach(function(item) {
									products.push({
										'id': item.id,
										'name': item.product_title,
										'quantity': item.quantity,
										'price': parseFloat(item.price)
									});
								});
							}
							response = products;
						}
						lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
						if (lastCart === null) {
							lastCart = {};
							if (!isProd)
								console.log('newsman remarketing: lastCart === null');
						}
						//check cache
						if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
							var objComparer = response;
							var missingProp = false;
							lastCart.forEach(e=>{
								if (!e.hasOwnProperty('name')) {
									missingProp = true;
								}
							}
							);
							if (missingProp)
								objComparer.forEach(function(v) {
									delete v.name
								});
							if (JSON.stringify(lastCart) === JSON.stringify(objComparer)) {
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is unchanged');
								lastCartFlag = true;
							} else {
								lastCartFlag = false;
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is changed');
							}
						}
						if (response.length > 0 && lastCartFlag == false) {
							nzmAddToCart(response);
						}//send only when on last request, products existed
						else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {
							nzmClearCart();
							if (!isProd)
								console.log('newsman remarketing: clear cart sent');
						} else {
							if (!isProd)
								console.log('newsman remarketing: request not sent');
						}
						firstLoad = false;
						bufferedXHR = false;
					} else {
						if (!isProd)
							console.log('newsman remarketing: response http status code is not 200');
						isError = true;
					}
				}
				try {
					xhr.send(null);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: error on xhr send');
					isError = true;
				}
			} else {
				if (!isProd)
					console.log('newsman remarketing: !buffered xhr || first load');
			}
		}
		function nzmClearCart() {
			_nzm.run('ec:setAction', 'clear_cart');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
			sessionStorage.setItem('lastCart', JSON.stringify([]));
			unlockClearCart = false;
		}
		function nzmAddToCart(response) {
			_nzm.run('ec:setAction', 'clear_cart');
			if (!isProd)
				console.log('newsman remarketing: clear cart sent, add to cart function');
			detailviewEvent(response);
		}
		function detailviewEvent(response) {
			if (!isProd)
				console.log('newsman remarketing: detailviewEvent execute');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, function() {
				if (!isProd)
					console.log('newsman remarketing: executing add to cart callback');
				var products = [];
				for (var item in response) {
					if (response[item].hasOwnProperty('id')) {
						_nzm.run('ec:addProduct', response[item]);
						products.push(response[item]);
					}
				}
				_nzm.run('ec:setAction', 'add');
				_nzm.run('send', 'event', 'UX', 'click', 'add to cart');
				sessionStorage.setItem('lastCart', JSON.stringify(products));
				unlockClearCart = true;
				if (!isProd)
					console.log('newsman remarketing: cart sent');
			});
		}
		function detectClicks() {
			window.addEventListener('click', function(event) {
				msClick = new Date();
			}, false);
		}
		function detectXHR() {
			var proxied = window.XMLHttpRequest.prototype.send;
			window.XMLHttpRequest.prototype.send = function() {
				var pointer = this;
				var validate = false;
				var timeValidate = false;
				var intervalId = window.setInterval(function() {
					if (pointer.readyState != 4) {
						return;
					}
					var msClickPassed = new Date();
					var timeDiff = msClickPassed.getTime() - msClick.getTime();
					if (timeDiff > 5000) {
						validate = false;
					} else {
						timeValidate = true;
					}
					var _location = pointer.responseURL;
					//own request exclusion
					if (timeValidate) {
						if (_location.indexOf('getCart.json') >= 0 || //magento 2.x
						_location.indexOf('/static/') >= 0 || _location.indexOf('/pub/static') >= 0 || _location.indexOf('/customer/section') >= 0 || //opencart 1
						_location.indexOf('getCart=true') >= 0 || //shopify
						_location.indexOf('cart.js') >= 0) {
							validate = false;
						} else {
							//check for engine name
							if (_nzmPluginInfo.indexOf('shopify') !== -1) {
								validate = true;
							} else {
								if (_location.indexOf(window.location.origin) !== -1)
									validate = true;
							}
						}
						if (validate) {
							bufferedXHR = true;
							if (!isProd)
								console.log('newsman remarketing: ajax request fired and catched from same domain, NewsmanAutoEvents called');
							NewsmanAutoEvents();
						}
					}
					clearInterval(intervalId);
				}, 1);
				return proxied.apply(this, [].slice.call(arguments));
			}
			;
		}

		//Newsman remarketing auto events