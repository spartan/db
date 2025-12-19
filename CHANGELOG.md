# Changelog
## [0.2.0](https://github.com/spartan/db/compare/v0.1.15...v0.2.0) (2025-12-19)


### Changes

* accept spartan/console:0.2 ([cab6713](https://github.com/spartan/db/commit/cab67139e626e72b210dbdf0bf1fab9839494a66))

### [0.1.15](https://github.com/spartan/db/compare/v0.1.14...v0.1.15) (2024-04-29)


### Fixes

* regression issue ([53f1ec2](https://github.com/spartan/db/commit/53f1ec22b1e2ea1da8a1bd719b864058894216f1))

### [0.1.14](https://github.com/spartan/db/compare/v0.1.13...v0.1.14) (2024-04-29)


### Changes

* php 8.1 support by upgrading to 2.0.0-beta2 ([406d7e2](https://github.com/spartan/db/commit/406d7e297a4ac7a3183ff714ed0688b257201e5f))

### [0.1.13](https://github.com/spartan/db/compare/v0.1.12...v0.1.13) (2021-12-14)


### Changes

* switch to propel/propel package ([292a845](https://github.com/spartan/db/commit/292a84545b245d4608c57e047e0b932d8e4f80f2))

### [0.1.12](https://github.com/spartan/db/compare/v0.1.11...v0.1.12) (2021-06-21)


### Fixes

* remove default path to config on migration commands - using fallback now ([0ab7535](https://github.com/spartan/db/commit/0ab753555b2885dba85c3cae1e4b946290eceaa8))

### [0.1.11](https://github.com/spartan/db/compare/v0.1.10...v0.1.11) (2021-06-06)


### Changes

* smart fallback for env config with migration commands ([a2ae44e](https://github.com/spartan/db/commit/a2ae44e082aac4c862c13bc61453c03089f141af))

### [0.1.10](https://github.com/spartan/db/compare/v0.1.9...v0.1.10) (2021-06-05)


### Fixes

* migration command env loading from globals ([d9e741d](https://github.com/spartan/db/commit/d9e741d057ec48fb487b6960afa3eca902812dcd))

### [0.1.9](https://github.com/spartan/db/compare/v0.1.8...v0.1.9) (2021-06-04)


### Fixes

* db migration commands fallback to global env when config file not found ([47131d8](https://github.com/spartan/db/commit/47131d82269c6a6b620071cb6bba8ab157a64797))

### [0.1.8](https://github.com/spartan/db/compare/v0.1.7...v0.1.8) (2021-04-29)


### Fixes

* migration:reset command ([9eeef2b](https://github.com/spartan/db/commit/9eeef2b84e2fc7bb1c4c4d7db57a8cb2082594c6))

### [0.1.7](https://github.com/spartan/db/compare/v0.1.6...v0.1.7) (2021-04-07)


### Fixes

* migration:diff command order of queries on foreign keys drop ([015ff08](https://github.com/spartan/db/commit/015ff08ae573d1c82554e310eda273502a7d5705))

### [0.1.6](https://github.com/spartan/db/compare/v0.1.5...v0.1.6) (2021-04-07)


### Fixes

* reset migration table on migration:reset ([90b4b9a](https://github.com/spartan/db/commit/90b4b9a28629d861218e308a6352658769a6b84f))

### [0.1.5](https://github.com/spartan/db/compare/v0.1.4...v0.1.5) (2021-03-31)


### Fixes

* migration diff missing end of query on rename column ([26f9171](https://github.com/spartan/db/commit/26f91719285e9b5579d256d52265bd53be611eda))

### [0.1.4](https://github.com/spartan/db/compare/v0.1.3...v0.1.4) (2021-03-10)


### Fixes

* migration commands namespace ([e5aceaf](https://github.com/spartan/db/commit/e5aceafa1fe65e0e0ac93ab11e7e920d8c78683a))


### New

* migration reset command ([459d6ab](https://github.com/spartan/db/commit/459d6ab520879238b9ee7d541fc6d993d9d0c5fc))

### [0.1.3](https://github.com/spartan/db/compare/v0.1.2...v0.1.3) (2021-03-07)


### New

* propel connect middleware class ([9f498c5](https://github.com/spartan/db/commit/9f498c51f0010decdad3ab1efffd7fc3c9e843e4))


### Fixes

* add missing env variables on provision ([8729539](https://github.com/spartan/db/commit/8729539d29f63e4544690719fb6b86662917ea23))


### Changes

* use Command::loadEnv static method instead ([5544fdd](https://github.com/spartan/db/commit/5544fddf32d2c39027e2f5892d02ce05ed2d1780))

### [0.1.2](https://github.com/spartan/db/compare/v0.1.1...v0.1.2) (2021-03-05)


### Changes

* move propel from require to suggest ([d13dfd5](https://github.com/spartan/db/commit/d13dfd5adf0c18f9657b0b3547104cdc7f845921))

### [0.1.1](https://github.com/spartan/db/compare/v0.1.0...v0.1.1) (2021-03-04)


### New

* spartan provision config ([a186402](https://github.com/spartan/db/commit/a186402050b1e59bd06aaf00d1922778b8e8c3b3))

## 0.1.0 (2021-03-03)


### New

* first release ([35f74a9](https://github.com/spartan/db/commit/35f74a91c0652910024459e76b70dcf7f4a36ad0))
