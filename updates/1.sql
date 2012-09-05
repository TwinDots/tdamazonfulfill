alter table shop_products
drop column x_amazon_fulfil;
alter table shop_products
add column x_amazon_fulfill bool;
alter table shop_products
add column x_amazon_sku varchar(45);