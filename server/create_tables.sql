CREATE TABLE rooms (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255)
); 


CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    room_id INT UNSIGNED,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL 
);  

 
create table categories (
    id int unsigned primary key auto_increment,
    name varchar(255) not null
);


create table products (
    id int unsigned primary key auto_increment,
    name varchar(255) not null,
    price decimal (10) not null,
    image varchar(255) not null,
    category_id int unsigned,
    foreign key (category_id) references categories(id),
    availability varchar(255) not null default 'available',
    created_at datetime not null default current_timestamp,
    deleted_at datetime default null
);

create table carts (
    id int unsigned primary key auto_increment,
    user_id int unsigned,
    foreign key (user_id) references users(id),
    updated_at datetime not null default current_timestamp on update current_timestamp
);

create table cart_products (
    id int unsigned primary key auto_increment,
    cart_id int unsigned,
    foreign key (cart_id) references carts(id),
    product_id int unsigned,
    foreign key (product_id) references products(id),
    quantity int unsigned
);

create table orders (
    id int unsigned primary key auto_increment,
    user_id int unsigned,
    foreign key (user_id) references users(id),
    status varchar(255) not null default 'processing',
    total_price decimal(10,2) not null,
    notes text,
    room_id int unsigned,
    foreign key (room_id) references rooms(id),
    created_at datetime not null default current_timestamp,
    updated_at datetime not null default current_timestamp on update current_timestamp,
    deleted_at datetime default null
);