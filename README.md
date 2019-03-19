# partner-library: Data pre-processing for talpa collector api

## Install using composer 

```
composer require talpa/binfmt
```

Install the command line tool



## Usting the tbfc command line tool

```
cat test/mock/demo.in.txt | bin/tbfc --tbfc --pack --stdin --stdout | bin/tbfc --tbfc --unpack --stdin --stdout
```

## Using tbfc command for batch processing

```
bin/tbfc --tbfc --pack --input=/path/*.csv --afterCmd='curl '
```


## Specs

- Maximum resolution: 1kHz (0.0001 seconds)
- Maximum space-saving shift between points: 6.5 sek
- Output buffer for gzip encoded data frames: 1500 Bytes (HTTP chunk size)


## Formats



## Tests 

Run the unit-test:

```
kick test
```
