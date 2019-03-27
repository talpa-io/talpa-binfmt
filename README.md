# partner-library: Data pre-processing for talpa collector api

## Install using composer 

```
sudo apt install php7.2-yaml
composer global config minimum-stability dev 
composer global require talpa/binfmt:dev-master
```

Install the command line tool


## Accepted Input Format (TabSV)

Line Format:

<timestamp_epoch>`\t`<signal_name>`\t`<measure_unit>`\t`<signal_value>`\n`

```
1550000000	signal1	rpm	5
1550000000.1234	signal1	rpm	10
```

No Header - UTF-8 Input only.


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
