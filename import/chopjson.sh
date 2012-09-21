if [ $# -ne 3 ]
then 
  echo "Usage $0 FILE NUMBER_OF_PARTS LENGTH_OF_ONE_OBJECT";
  exit 1;
fi

file=$1
n=$2
size_instance=$3


l=`cat $file | wc -l` # length (number of lines)
instances=`echo "($l-2)/$size_instance" | bc` # number of instances
part=`expr $instances / $n` # number of instances per segment
echo "Chopping up $1 in $n parts of $part objects of $size_instance lines"

length=`echo "($part*$size_instance)-1" | bc` # length of each segment (minus the '}')
for i in $(eval echo {1..$n})
do
  window=`echo "($i *($part*$size_instance))" | bc`
  echo "Creating $file.$i [window: $window, length: $length]"
  echo -e '[\n' > "$file.$i"
  cat "$file" | head -n $window | tail -n $length >> "$file.$i"
  echo -e '  }\n]' >> "$file.$i"
done
