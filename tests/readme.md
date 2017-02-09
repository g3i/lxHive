# Structure

* Folder `tdd`: Contains lxHive PHPUnit tests
* Folder `bdd`: You can safely load external BDD suites in this folder, they are excluded from commits by the local `.gitignore`

## External test suites

### [ADL LRS Conformance Test Suite](https://github.com/adlnet/lrs-conformance-test-suite)

* tests the 'MUST' requirements of the xAPI Spec based on the ADL [testing requirements](https://github.com/adlnet/xapi-lrs-conformance-requirements)
* requires npm and nodejs

```bash
cd tests/bdd
git clone https://github.com/adlnet/lrs-conformance-test-suite.git
cd lrs-conformance-test-suite && npm install
```

2. Follow the [setup and usage instructions](https://github.com/adlnet/lrs-conformance-test-suite)
