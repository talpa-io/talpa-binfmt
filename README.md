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


## GPS Geotagging

see [info](https://stackoverflow.com/questions/15965166/what-is-the-maximum-length-of-latitude-and-longitude)

Lontitude: -180 - +180
Latitude: -90 - +90
Height: -8000 - +20000m

10cm Prcision: 0.000001 (6 decimal places)
1mm Precision: 8 decimal places
1cm Precision: 7 decimal places !!

=> signedInt_32 (wertebereich: −2.147.483.648 - 2.147.483.647)

Position: 

Datatype:

SET_LNG_LAT_POS: sig_int_32, sig_int_32
SET_LNG_LAT_HEIGHT_POS: sig_int_32, sig_int_32, sig_int_32

SET_LNG_LAT_DIFF_8: sig_int_8, sig_int_8 (bis zu 1,2 m / sek = 4.32 km/h)
SET_LNG_LAT_DIFF_16: sig_int_16, sig_int_16 (bis zu 32m / sek = 115 km/h )





## Tests 

Run the unit-test:

```
kick test
```
